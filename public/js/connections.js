/* Extracted from resources/views/connections/index.blade.php — cacheable. */
        function switchConnTab(tabName) {
            const tabs = ['accepted', 'incoming', 'outgoing', 'search'];
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
            const hash = location.hash.replace('#', '');
            if (['accepted', 'incoming', 'outgoing', 'search'].includes(hash)) {
                switchConnTab(hash);
            }
        });
