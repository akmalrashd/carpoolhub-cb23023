/* Extracted from resources/views/home.blade.php — cacheable. */
        function hpToggleMobileActions(btn) {
            var panel = document.getElementById('hp-mobile-extra-actions');
            if (!panel) return;
            var open = panel.classList.toggle('open');
            btn.textContent = open ? 'Show less' : 'View all';
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

        /* ── Skeleton fade-out on page ready ─────────────────────── */
        function hpInitSkeletons() {
            // Stats skeleton overlay → fade out (real content always visible underneath)
            var statSkel = document.getElementById('hp-stats-skel-overlay');
            if (statSkel) {
                setTimeout(function () {
                    statSkel.classList.add('loaded');
                }, 280);
            }

            // Trips skeleton → real content
            var tripsSkel = document.getElementById('hp-trips-skel');
            var tripsReal = document.getElementById('hp-trips-real');
            if (tripsSkel && tripsReal) {
                setTimeout(function () {
                    tripsSkel.style.opacity = '0';
                    tripsSkel.style.pointerEvents = 'none';
                    tripsReal.style.opacity = '1';
                    setTimeout(function () { tripsSkel.style.display = 'none'; }, 350);
                }, 320);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', hpInitSkeletons);
        } else {
            hpInitSkeletons();
        }
