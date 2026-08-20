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

        function hpGoToCarouselPage(trackId, index) {
            var track = document.getElementById(trackId);
            if (!track) return;
            track.scrollTo({ left: index * track.clientWidth, behavior: 'smooth' });
        }

        function hpInitAutoCarousel(trackId, dotsId, intervalMs) {
            var track = document.getElementById(trackId);
            var dotsWrap = dotsId ? document.getElementById(dotsId) : null;
            if (!track) return;
            var slideCount = track.querySelectorAll('.hp-pub-carousel-slide').length;
            if (slideCount < 2) return;
            var dots = dotsWrap ? dotsWrap.querySelectorAll('.hp-pub-carousel-dot') : [];

            var currentIndex = function () {
                var maxScroll = track.scrollWidth - track.clientWidth;
                var ratio = maxScroll > 0 ? track.scrollLeft / maxScroll : 0;
                return Math.round(ratio * (slideCount - 1));
            };
            var updateDots = function () {
                var idx = currentIndex();
                dots.forEach(function (d, i) {
                    d.classList.toggle('active', i === idx);
                });
            };

            var timer = null;
            var resumeTimeout = null;
            var stopAuto = function () {
                if (timer) { window.clearInterval(timer); timer = null; }
            };
            var startAuto = function () {
                stopAuto();
                timer = window.setInterval(function () {
                    if (track.offsetParent === null) return; // hidden at this breakpoint
                    var next = (currentIndex() + 1) % slideCount;
                    track.scrollTo({ left: next * track.clientWidth, behavior: 'smooth' });
                }, intervalMs);
            };
            var pauseThenResume = function () {
                stopAuto();
                if (resumeTimeout) window.clearTimeout(resumeTimeout);
                resumeTimeout = window.setTimeout(startAuto, 6000);
            };

            track.addEventListener('scroll', updateDots, { passive: true });
            track.addEventListener('touchstart', pauseThenResume, { passive: true });
            track.addEventListener('pointerdown', pauseThenResume);

            updateDots();
            startAuto();
        }
        document.addEventListener('DOMContentLoaded', function () {
            hpInitAutoCarousel('hp-pub-mobile-carousel-track', 'hp-pub-mobile-carousel-dots', 4000);
            hpInitAutoCarousel('hp-pub-desktop-carousel-track', 'hp-pub-desktop-carousel-dots', 5000);
        });

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


