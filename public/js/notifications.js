/* Extracted from resources/views/notifications/index.blade.php — cacheable. */
(function () {
    // This page never had a <meta name="csrf-token"> tag — that element does
    // not exist anywhere in the layout, only an inline `csrfToken` JS var — so
    // every fetch below was sending an empty X-CSRF-TOKEN, failing Laravel's
    // CSRF check with a silent 419 that the `if (!r.ok) return;` guards below
    // swallowed without any visible error. All four actions on this page were
    // silently doing nothing. Matches the window.CH_TRIPS.csrf / window.
    // CH_PAYMENTS.csrf pattern those pages already use correctly.
    var csrf = (window.CH_NOTIFICATIONS || {}).csrf || '';

    function fadeRemove(row) {
        row.style.transition = 'opacity .2s, max-height .25s';
        row.style.opacity = '0';
        row.style.overflow = 'hidden';
        setTimeout(function () { row.remove(); }, 250);
    }

    function adjustUnreadBadge(delta) {
        var badge = document.querySelector('.notif-unread-badge');
        if (!badge) return;
        var current = parseInt(badge.textContent.replace(/\D/g, ''), 10) || 0;
        var next = current + delta;
        if (next <= 0) {
            badge.style.display = 'none';
        } else {
            badge.innerHTML = '<i class="fa-solid fa-bell"></i> ' + next + ' Unread';
        }
    }

    /* ── Click anywhere on a row to open it (except the delete button) ── */
    function openRow(row) {
        var url = row.dataset.openUrl;
        if (url) window.location.href = url;
    }

    document.addEventListener('click', function (e) {
        var row = e.target.closest('.notif-row');
        if (!row) return;
        if (e.target.closest('.notif-row-actions')) return;
        openRow(row);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        var row = e.target.closest('.notif-row');
        if (!row) return;
        e.preventDefault();
        openRow(row);
    });

    /* ── Delete notification (AJAX, confirmed) ── */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.notif-delete-btn');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        if (!window.confirm('Delete this notification? This cannot be undone.')) return;

        var wasUnread = btn.dataset.wasUnread === '1';
        fetch(btn.dataset.url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            credentials: 'same-origin',
        }).then(function (r) {
            if (!r.ok) return;
            var row = btn.closest('.notif-row');
            if (row) fadeRemove(row);
            if (wasUnread) adjustUnreadBadge(-1);
            if (window.showToast) {
                window.showToast("Notification deleted.", "success");
            }
        }).catch(function () {});
    });

    /* ── Delete all notifications (AJAX, confirmed) ── */
    var deleteAllBtn = document.getElementById('notif-delete-all-btn');
    if (deleteAllBtn) {
        deleteAllBtn.addEventListener('click', function () {
            if (!document.querySelector('.notif-row')) {
                if (window.showToast) window.showToast('No notifications to delete.', 'info');
                return;
            }
            if (!window.confirm('Delete ALL notifications? This cannot be undone.')) return;

            fetch(deleteAllBtn.dataset.url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
            }).then(function (r) {
                if (!r.ok) return;
                document.querySelectorAll('.notif-row').forEach(fadeRemove);
                if (window.showToast) {
                    window.showToast("All notifications deleted.", "success");
                }
                // The tab counts and pagination are server-rendered — deleting
                // everything makes those stale, so reload instead of trying to
                // patch every derived number by hand.
                window.setTimeout(function () { window.location.reload(); }, 700);
            }).catch(function () {});
        });
    }

    /* ── Mark all as read (AJAX) ── */
    var markAllBtn = document.getElementById('notif-mark-all-btn');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function () {
            if (!document.querySelector('.notif-row.is-unread')) {
                if (window.showToast) window.showToast('No unread notifications.', 'info');
                return;
            }
            var form = document.getElementById('notif-mark-all-form');
            fetch(form.action, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json',
                           'Content-Type': 'application/x-www-form-urlencoded' },
                body: '_method=PATCH',
                credentials: 'same-origin',
            }).then(function (r) {
                if (!r.ok) return;
                document.querySelectorAll('.notif-row.is-unread').forEach(function (row) {
                    row.classList.remove('is-unread');
                    row.classList.add('is-read');
                    row.style.borderLeft = '';
                    row.style.background = '';
                });
                var badge = document.querySelector('.notif-unread-badge');
                if (badge) badge.style.display = 'none';
                if (window.showToast) {
                    window.showToast("All notifications marked as read.", "success");
                }
            }).catch(function () {});
        });
    }
})();
