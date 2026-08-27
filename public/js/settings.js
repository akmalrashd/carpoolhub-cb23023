/* Extracted from resources/views/settings/index.blade.php — cacheable. */
        // ── Tab Switcher Logic ───────────────────────────────────────────
        function switchSettingsTab(tabName) {
            const tabs = ['profile', 'payment', 'security', 'notifications'];
            if (!tabs.includes(tabName)) return;

            tabs.forEach(t => {
                const btn = document.getElementById(`nav-btn-${t}`);
                const panel = document.getElementById(`panel-${t}`);
                if (btn && panel) {
                    if (t === tabName) {
                        btn.classList.add('is-active');
                        btn.setAttribute('aria-selected', 'true');
                        panel.classList.add('is-active');
                    } else {
                        btn.classList.remove('is-active');
                        btn.setAttribute('aria-selected', 'false');
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
                    const hint = document.getElementById('avatarPendingHint');
                    if (hint) {
                        hint.hidden = false;
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

        // ── Password Strength Meter ──────────────────────────────────────
        function handlePasswordStrength(value) {
            const meter = document.getElementById('pwStrengthMeter');
            const fill = document.getElementById('pwStrengthFill');
            const label = document.getElementById('pwStrengthLabel');
            if (!meter || !fill || !label) return;

            if (!value) {
                meter.hidden = true;
                meter.classList.remove('is-weak', 'is-fair', 'is-good', 'is-strong');
                handlePasswordMatch();
                return;
            }
            meter.hidden = false;

            let score = 0;
            if (value.length >= 8) score++;
            if (value.length >= 12) score++;
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
            if (/\d/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value)) score++;
            score = Math.min(score, 4);

            const levels = [
                { cls: 'is-weak', width: '20%', text: 'Weak' },
                { cls: 'is-weak', width: '40%', text: 'Weak' },
                { cls: 'is-fair', width: '60%', text: 'Fair' },
                { cls: 'is-good', width: '80%', text: 'Good' },
                { cls: 'is-strong', width: '100%', text: 'Strong' },
            ];
            const level = levels[score];

            meter.classList.remove('is-weak', 'is-fair', 'is-good', 'is-strong');
            meter.classList.add(level.cls);
            fill.style.width = level.width;
            label.textContent = level.text;

            handlePasswordMatch();
        }

        // ── Live Password-Match Check ────────────────────────────────────
        function handlePasswordMatch() {
            const newPassword = document.getElementById('newPassword');
            const confirmPassword = document.getElementById('confirmPassword');
            const hint = document.getElementById('pwMatchHint');
            if (!newPassword || !confirmPassword || !hint) return;

            const confirmValue = confirmPassword.value;
            hint.classList.remove('is-match', 'is-mismatch');

            if (!confirmValue) {
                hint.textContent = '';
                return;
            }

            if (confirmValue === newPassword.value) {
                hint.classList.add('is-match');
                hint.innerHTML = '<i class="fa-solid fa-circle-check"></i> Passwords match';
            } else {
                hint.classList.add('is-mismatch');
                hint.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Passwords do not match';
            }
        }

        // ── Submit Feedback: disable + spinner while a save is in flight ──
        function bindSettingsFormLoadingState() {
            document.querySelectorAll('.settings-panel-card form').forEach(function (form) {
                form.addEventListener('submit', function () {
                    const tab = form.getAttribute('data-tab');
                    if (tab) {
                        try {
                            sessionStorage.setItem('ch_settings_last_tab', tab);
                        } catch (e) { /* storage unavailable — non-fatal */ }
                    }

                    const btn = form.querySelector('.btn-submit-yellow');
                    if (btn && !btn.disabled) {
                        btn.dataset.originalHtml = btn.innerHTML;
                        btn.disabled = true;
                        btn.classList.add('is-loading');
                        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
                    }
                });
            });
        }

        // ── Resolve which tab should be active on load ───────────────────
        // Priority: a validation error (blade-computed) > URL hash > the tab the
        // user was last saving from (sessionStorage, set on submit above).
        function resolveInitialSettingsTab() {
            const tabs = ['profile', 'payment', 'security', 'notifications'];
            const container = document.querySelector('.profile-page-container');
            const errorTab = container ? container.getAttribute('data-error-tab') : '';
            if (errorTab && tabs.includes(errorTab)) {
                return errorTab;
            }

            const hash = location.hash.replace('#', '');
            if (tabs.includes(hash)) {
                return hash;
            }

            let storedTab = null;
            try {
                storedTab = sessionStorage.getItem('ch_settings_last_tab');
                sessionStorage.removeItem('ch_settings_last_tab');
            } catch (e) { /* storage unavailable — non-fatal */ }
            if (storedTab && tabs.includes(storedTab)) {
                return storedTab;
            }

            return null;
        }

        // ── Phone number auto-format: +60 XX-XXX XXXX ──────────────────────
        // Same single-field behaviour as the registration page's #phone
        // field (public/js/auth-register.js) — no country-code selector,
        // +60 is pre-filled and stays anchored, digits get grouped as typed,
        // and the "011" prefix gets its one extra local digit.
        function formatMyPhone(raw) {
            let digits = raw.replace(/\D/g, '');
            if (digits.startsWith('60')) digits = digits.slice(2);
            if (digits.startsWith('0')) digits = digits.slice(1);

            const isElevenPrefix = digits.slice(0, 2) === '11';
            digits = digits.slice(0, isElevenPrefix ? 10 : 9);

            let formatted = '+60';
            if (digits.length) formatted += ' ' + digits.slice(0, 2);
            if (isElevenPrefix) {
                if (digits.length > 2) formatted += '-' + digits.slice(2, 6);
                if (digits.length > 6) formatted += ' ' + digits.slice(6, 10);
            } else {
                if (digits.length > 2) formatted += '-' + digits.slice(2, 5);
                if (digits.length > 5) formatted += ' ' + digits.slice(5, 9);
            }
            return formatted;
        }

        function bindPhoneNumberFormatting() {
            const phoneInput = document.getElementById('profilePhone');
            if (!phoneInput) return;

            const PREFIX = '+60 ';

            const applyFormat = () => {
                const cursorFromEnd = phoneInput.value.length - phoneInput.selectionStart;
                phoneInput.value = formatMyPhone(phoneInput.value);
                const pos = Math.max(phoneInput.value.length - cursorFromEnd, PREFIX.length);
                phoneInput.setSelectionRange(pos, pos);
            };

            const clearIfEmpty = () => {
                if (phoneInput.value.trim() === '+60') {
                    phoneInput.value = '';
                }
            };

            phoneInput.value = phoneInput.value ? formatMyPhone(phoneInput.value) : PREFIX;

            phoneInput.addEventListener('input', applyFormat);
            phoneInput.addEventListener('focus', () => {
                if (!phoneInput.value) phoneInput.value = PREFIX;
            });
            phoneInput.addEventListener('blur', clearIfEmpty);
            if (phoneInput.form) {
                phoneInput.form.addEventListener('submit', clearIfEmpty);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const target = resolveInitialSettingsTab();
            if (target) {
                switchSettingsTab(target);
            }
            bindSettingsFormLoadingState();
            bindPhoneNumberFormatting();
        });
