/* Extracted from resources/views/notifications/index.blade.php — cacheable. */
(function () {
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

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

    /* ── Mark as read (AJAX, no reload) ── */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.notif-mark-read-btn');
        if (!btn) return;
        e.preventDefault();
        fetch(btn.dataset.url, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            credentials: 'same-origin',
        }).then(function (r) {
            if (!r.ok) return;
            var row = btn.closest('.notif-row');
            if (!row) return;
            row.classList.remove('is-unread');
            row.classList.add('is-read');
            row.style.borderLeft = '';
            row.style.background = '';
            btn.remove();
            adjustUnreadBadge(-1);
            if (window.showToast) {
                window.showToast("Notification marked as read.", "success");
            }
        }).catch(function () {});
    });

    /* ── Delete notification (AJAX) ── */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.notif-delete-btn');
        if (!btn) return;
        e.preventDefault();
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

    /* ── Clear all read (AJAX) ── */
    var clearBtn = document.getElementById('notif-clear-read-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            fetch(clearBtn.dataset.url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
            }).then(function (r) {
                if (!r.ok) return;
                document.querySelectorAll('.notif-row.is-read').forEach(fadeRemove);
                if (window.showToast) {
                    window.showToast("Read notifications cleared.", "success");
                }
            }).catch(function () {});
        });
    }

    /* ── Mark all as read (AJAX) ── */
    var markAllBtn = document.getElementById('notif-mark-all-btn');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function () {
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
                    var readBtn = row.querySelector('.notif-mark-read-btn');
                    if (readBtn) readBtn.remove();
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
