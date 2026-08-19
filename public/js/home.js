/* Extracted from resources/views/home.blade.php — cacheable. */
        function hpInitQuickCarousel(trackId, dotsId) {
            var track = document.getElementById(trackId);
            var dotsWrap = document.getElementById(dotsId);
            if (!track || !dotsWrap) return;
            var dots = dotsWrap.querySelectorAll('.hp-mobile-quick-dot');
            if (!dots.length) return;
            var update = function () {
                var maxScroll = track.scrollWidth - track.clientWidth;
                var ratio = maxScroll > 0 ? track.scrollLeft / maxScroll : 0;
                var idx = Math.round(ratio * (dots.length - 1));
                dots.forEach(function (d, i) {
                    d.classList.toggle('active', i === idx);
                });
            };
            track.addEventListener('scroll', update, { passive: true });
            update();
        }
        document.addEventListener('DOMContentLoaded', function () {
            hpInitQuickCarousel('hp-mobile-quick-track', 'hp-mobile-quick-dots');
        });

        function hpGoToQuickPage(index) {
            var track = document.getElementById('hp-mobile-quick-track');
            if (!track) return;
            track.scrollTo({ left: index * track.clientWidth, behavior: 'smooth' });
        }

        function hpSwitchTab(panel, btn, prefix) {
            prefix = prefix || 'hp';
            // Hide all panels
            document.getElementById(prefix + '-tab-driver').style.display    = 'none';
            document.getElementById(prefix + '-tab-passenger').style.display = 'none';
            // Show target panel
            document.getElementById(prefix + '-tab-' + panel).style.display = 'block';
            // Update tab active state
            document.querySelectorAll('#' + prefix + '-trip-tabs .hp-tab').forEach(function(t) {
                t.classList.remove('active');
            });
            btn.classList.add('active');
        }


