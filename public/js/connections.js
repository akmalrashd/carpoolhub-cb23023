/* Connections Page Logic - Cacheable */
function switchConnTab(tabName) {
    const tabs = ['accepted', 'incoming', 'outgoing'];
    if (!tabs.includes(tabName)) return;

    tabs.forEach(t => {
        const btn = document.getElementById(`tab-btn-${t}`);
        const panel = document.getElementById(`panel-${t}`);
        if (btn && panel) {
            if (t === tabName) {
                btn.classList.add('is-active');
                panel.classList.add('is-active');
            } else {
                btn.classList.remove('is-active');
                panel.classList.remove('is-active');
            }
        }
    });

    if (history.pushState) {
        history.pushState(null, null, `#${tabName}`);
    } else {
        location.hash = `#${tabName}`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Tab switching based on hash
    const hash = location.hash.replace('#', '');
    if (['accepted', 'incoming', 'outgoing'].includes(hash)) {
        switchConnTab(hash);
    }

    // Modal controls
    const openBtn = document.getElementById('openFindModalBtn');
    const closeBtn = document.getElementById('closeFindModalBtn');
    const modal = document.getElementById('findCarpoolersModal');
    const searchInput = document.getElementById('modalSearchInput');

    const openModal = () => {
        if (!modal) return;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        if (searchInput) {
            setTimeout(() => searchInput.focus(), 100);
        }
    };

    const closeModal = () => {
        if (!modal) return;
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    };

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }

    // Auto-open search modal if URL has search parameters or #search hash
    if (window.location.search.includes('q=') || window.location.search.includes('open_modal=1') || window.location.hash === '#search') {
        openModal();
    }

    // Extract add connection listeners so they can be re-applied after AJAX search
    const attachAddConnectionListeners = (container = document) => {
        const addConnForms = container.querySelectorAll('.add-connection-form');
        addConnForms.forEach(form => {
            if (form.classList.contains('js-listener-attached')) return;
            form.classList.add('js-listener-attached');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = form.querySelector('button[type="submit"]');
                const originalContent = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';
                btn.disabled = true;
                
                try {
                    const formData = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': formData.get('_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        },
                        body: formData
                    });
                    
                    if (response.ok) {
                        const parent = form.parentNode;
                        form.remove();
                        const badge = document.createElement('span');
                        badge.className = 'badge-status-pending';
                        badge.innerHTML = '<i class="fa-solid fa-clock"></i> Sent';
                        parent.appendChild(badge);
                    } else {
                        console.error('Error adding connection');
                        btn.innerHTML = originalContent;
                        btn.disabled = false;
                    }
                } catch (err) {
                    console.error(err);
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            });
        });
    };

    // Initial attach
    attachAddConnectionListeners();

    // Prevent full page reload on search form submit
    const searchForm = document.getElementById('modalSearchForm');
    const resultsArea = document.getElementById('modalResultsArea');
    if (searchForm) {
        searchForm.addEventListener('submit', e => e.preventDefault());
    }

    // Server-side AJAX search as user types
    if (searchInput && resultsArea) {
        let debounceTimer;
        // The typed query is echoed straight back into this loading message. It
        // can also arrive pre-filled from ?q= on the page (Blade escapes it
        // there, but reading it back out via .value hands JS the raw text), so
        // without escaping here a crafted link executes script the moment the
        // field re-renders — this is the same helper explore-index.js and
        // trips-requests.js already use for the same reason.
        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(async () => {
                // Show loading state
                resultsArea.innerHTML = `
                    <div class="modal-empty-state">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <p class="modal-empty-title">Searching...</p>
                        <p class="modal-empty-sub">Looking for "${escapeHtml(query)}"...</p>
                    </div>
                `;
                
                try {
                    const params = new URLSearchParams();
                    if (query) params.append('q', query);
                    params.append('open_modal', '1');
                    
                    const response = await fetch(`/connections?${params.toString()}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    
                    if (response.ok) {
                        const html = await response.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newResultsArea = doc.getElementById('modalResultsArea');
                        
                        if (newResultsArea) {
                            resultsArea.innerHTML = newResultsArea.innerHTML;
                            attachAddConnectionListeners(resultsArea);
                        }
                    }
                } catch (err) {
                    console.error('AJAX search failed', err);
                    resultsArea.innerHTML = `
                        <div class="modal-empty-state">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <p class="modal-empty-title">Search Failed</p>
                            <p class="modal-empty-sub">Please try again later.</p>
                        </div>
                    `;
                }
            }, 400); // 400ms debounce
        });
    }
});
