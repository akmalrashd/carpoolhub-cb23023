/* Extracted from resources/views/home.blade.php — cacheable. */
        function hpToggleMobileActions(btn) {
            var panel = document.getElementById('hp-mobile-extra-actions');
            if (!panel) return;
            var open = panel.classList.toggle('open');
            var textSpan = btn.querySelector('span');
            var icon = btn.querySelector('i');
            if (textSpan) {
                textSpan.textContent = open ? 'Show less' : 'View all';
            } else {
                btn.textContent = open ? 'Show less' : 'View all';
            }
            if (icon) {
                if (open) {
                    icon.style.transform = 'rotate(90deg)';
                } else {
                    icon.style.transform = 'none';
                }
            }
        }

        function hpSwitchTab(panel, btn) {
            // Hide all panels
            document.getElementById('hp-tab-driver').style.display    = 'none';
            document.getElementById('hp-tab-passenger').style.display = 'none';
            // Show target panel
            document.getElementById('hp-tab-' + panel).style.display = 'block';
            // Update tab active state
            document.querySelectorAll('#hp-trip-tabs .hp-tab').forEach(function(t) {
                t.classList.remove('active');
            });
            btn.classList.add('active');
        }


