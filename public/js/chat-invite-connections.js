/* "Invite Connections" picker — lives at the shell level (loaded once on
   chats/index.blade.php and chats/show.blade.php, NOT inside the swappable
   thread partial), because chat-thread-controller.js replaces the thread
   pane's markup on every chat switch without reloading the page. Delegating
   every binding on document (matching the trigger by id, reading its own
   data-* for URLs) means this file never needs to re-run when a new
   #chatInviteBtn appears in freshly-swapped thread content — same pattern
   already proven by rate-trip-modal.js / trip-details-modal.js. */
(() => {
    const modal = document.getElementById('inviteConnectionsModal');
    const list = document.getElementById('inviteConnectionsList');
    const search = document.getElementById('inviteConnectionsSearch');
    const closeBtn = document.getElementById('inviteConnectionsClose');
    const cancelBtn = document.getElementById('inviteConnectionsCancelBtn');
    const submitBtn = document.getElementById('inviteConnectionsSubmitBtn');
    const countEl = document.getElementById('inviteConnectionsCount');
    if (!modal || !list) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    const updateCount = () => {
        const checked = list.querySelectorAll('input[type="checkbox"]:checked:not(:disabled)').length;
        if (countEl) countEl.textContent = `Invite (${checked})`;
        if (submitBtn) submitBtn.disabled = checked === 0;
    };

    const renderList = (connections) => {
        if (connections.length === 0) {
            list.innerHTML = '<div style="padding:16px; color:var(--muted);">You have no accepted connections to invite yet.</div>';
            return;
        }

        // Already-in-chat connections sink to the bottom — greyed out and
        // locked (see the disabled checkbox below), so the ones an admin
        // can actually act on stay first rather than mixed in.
        const sorted = [...connections].sort((a, b) => Number(a.is_member) - Number(b.is_member));

        list.style.gap = '0';
        list.innerHTML = sorted.map((c, i) => `
            <label
                data-search="${escapeHtml(`${c.name} ${c.email}`.toLowerCase())}"
                style="display:flex; flex-direction:row; align-items:center; gap:12px; padding:8px 2px; ${i < sorted.length - 1 ? 'border-bottom:1px solid var(--hairline);' : ''} cursor:${c.is_member ? 'default' : 'pointer'}; opacity:${c.is_member ? '0.5' : '1'};"
            >
                <span style="width:40px; height:40px; border-radius:999px; border:2px solid var(--hairline-strong); display:grid; place-items:center; font-size:16px; font-weight:800; font-family:var(--font-display), sans-serif; flex-shrink:0; overflow:hidden; ${window.CarpoolAvatar.bgStyle(c.id)}">${window.CarpoolAvatar.innerHtml({ name: c.name, id: c.id })}</span>
                <div style="flex:1; min-width:0;">
                    <div style="font-family:var(--font-display), sans-serif; font-size:14px; font-weight:800; color:var(--ink);">${escapeHtml(c.name)}</div>
                    <div style="font-size:12px; color:var(--muted); margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(c.email)}</div>
                </div>
                <input type="checkbox" value="${escapeHtml(c.id)}" ${c.is_member ? 'checked disabled' : ''} style="width:20px; height:20px; flex-shrink:0;">
            </label>
        `).join('');

        updateCount();
    };

    window.CarpoolBottomSheet?.enable({
        modal,
        card: modal.querySelector('.trip-payment-review-card'),
        head: modal.querySelector('.trip-payment-review-head'),
        closeFn: close,
    });
    closeBtn?.addEventListener('click', close);
    cancelBtn?.addEventListener('click', close);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) close();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
    });
    list.addEventListener('change', updateCount);
    search?.addEventListener('input', () => {
        const term = search.value.trim().toLowerCase();
        list.querySelectorAll('[data-search]').forEach((row) => {
            row.hidden = term !== '' && !row.dataset.search.includes(term);
        });
    });
    submitBtn?.addEventListener('click', () => {
        const ids = Array.from(list.querySelectorAll('input[type="checkbox"]:checked:not(:disabled)')).map((el) => el.value);
        const inviteUrl = modal.dataset.inviteUrl;
        if (ids.length === 0 || !inviteUrl) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = inviteUrl;
        form.innerHTML = `<input type="hidden" name="_token" value="${escapeHtml(csrf)}">`
            + ids.map((id) => `<input type="hidden" name="connection_user_ids[]" value="${id}">`).join('');
        document.body.appendChild(form);
        form.submit();
    });

    document.addEventListener('click', async (event) => {
        const inviteBtn = event.target instanceof Element ? event.target.closest('#chatInviteBtn') : null;
        if (!inviteBtn) return;

        const pickerOptionsUrl = inviteBtn.dataset.pickerOptionsUrl;
        const inviteUrl = inviteBtn.dataset.inviteUrl;
        if (!pickerOptionsUrl || !inviteUrl) return;
        modal.dataset.inviteUrl = inviteUrl;

        if (search) search.value = '';
        list.style.gap = '0';
        list.innerHTML = '<div style="padding:16px; color:var(--muted);">Loading...</div>';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        let connections = [];
        try {
            const response = await fetch(pickerOptionsUrl, { headers: { Accept: 'application/json' } });
            const payload = await response.json();
            connections = payload.connections || [];
        } catch {
            list.innerHTML = '<div style="padding:16px; color:var(--danger-ink,#dc2626);">Could not load your connections. Please try again.</div>';
            return;
        }

        renderList(connections);
    });
})();
