/* Extracted from resources/views/settings/index.blade.php — cacheable. */
        // ── Tab Switcher Logic ───────────────────────────────────────────
        function switchSettingsTab(tabName) {
            const tabs = ['profile', 'payment', 'security'];
            if (!tabs.includes(tabName)) return;

            tabs.forEach(t => {
                const btn = document.getElementById(`nav-btn-${t}`);
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

        // ── Password Visibility Toggle ───────────────────────────────────
        function togglePassVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input && icon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        }

        // ── Avatar Preview ───────────────────────────────────────────────
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const heroAvatar = document.querySelector('.settings-hero-avatar');
                    if (heroAvatar) {
                        heroAvatar.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // ── QR Code Image Preview ─────────────────────────────────────────
        function previewQr(input, imgId, emptyIconId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.getElementById(imgId);
                    const icon = document.getElementById(emptyIconId);
                    if (img) {
                        img.src = e.target.result;
                        img.style.display = 'block';
                    }
                    if (icon) {
                        icon.style.display = 'none';
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // ── Check Initial Hash on Page Load ──────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            const hash = location.hash.replace('#', '');
            if (['profile', 'payment', 'security'].includes(hash)) {
                switchSettingsTab(hash);
            }
        });
