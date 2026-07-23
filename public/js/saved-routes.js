/* Extracted from resources/views/saved-routes/index.blade.php — cacheable. */
    (function () {
        var searchInput = document.getElementById('srSearchInput');
        var cards = Array.prototype.slice.call(document.querySelectorAll('#srGrid [data-route-name]'));
        var emptyMsg = document.getElementById('srSearchEmpty');

        function normalize(text) { return (text || '').toLowerCase().trim(); }

        function applyFilter() {
            var query = normalize(searchInput.value);
            var visible = 0;
            cards.forEach(function (card) {
                var match = normalize(card.getAttribute('data-route-name')).indexOf(query) !== -1;
                card.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (emptyMsg) emptyMsg.style.display = visible ? 'none' : 'block';
        }

        if (searchInput) searchInput.addEventListener('input', applyFilter);

        function toNumber(value) {
            var parsed = Number.parseFloat(String(value || '').trim());
            return Number.isFinite(parsed) ? parsed : null;
        }

        function formatDistance(meters) {
            if (!Number.isFinite(meters)) return '—';
            var km = meters / 1000;
            return km >= 10 ? km.toFixed(0) + ' km' : km.toFixed(1) + ' km';
        }

        function formatDuration(seconds) {
            if (!Number.isFinite(seconds)) return '—';
            var minutes = Math.max(1, Math.round(seconds / 60));
            if (minutes >= 60) {
                var hours = Math.floor(minutes / 60);
                var rest = minutes % 60;
                return rest ? hours + 'h ' + rest + 'm' : hours + 'h';
            }
            return minutes + ' min';
        }

        function haversineMeters(aLat, aLng, bLat, bLng) {
            var radius = 6371000;
            var toRad = function (deg) { return deg * Math.PI / 180; };
            var dLat = toRad(bLat - aLat);
            var dLng = toRad(bLng - aLng);
            var lat1 = toRad(aLat);
            var lat2 = toRad(bLat);
            var h = Math.sin(dLat / 2) * Math.sin(dLat / 2)
                + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
            return 2 * radius * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
        }

        function updateRouteStats(card, distanceMeters, durationSeconds) {
            var distanceEl = card.querySelector('[data-route-distance]');
            var timeEl = card.querySelector('[data-route-time]');
            if (distanceEl) distanceEl.textContent = formatDistance(distanceMeters);
            if (timeEl) timeEl.textContent = formatDuration(durationSeconds);
        }

        function fallbackRoute(card, points) {
            var distance = 0;
            for (var index = 1; index < points.length; index++) {
                distance += haversineMeters(points[index - 1][0], points[index - 1][1], points[index][0], points[index][1]) * 1.25;
            }
            var duration = distance / (32 * 1000 / 3600);
            updateRouteStats(card, distance, duration);
            return points;
        }

        function renderMiniMap(card, coordinates, markers) {
            if (!window.L) return;
            var mapEl = card.querySelector('[data-route-map]');
            if (!mapEl || mapEl.dataset.ready === '1') return;
            mapEl.dataset.ready = '1';

            var map = window.L.map(mapEl, {
                zoomControl: false,
                attributionControl: false,
                dragging: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false,
                tap: false,
            });

            window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                crossOrigin: true,
            }).addTo(map);

            window.L.polyline(coordinates, {
                color: '#facc15',
                weight: 5,
                opacity: .95,
                lineCap: 'round',
                lineJoin: 'round',
            }).addTo(map);
            var boundsPoints = coordinates.slice();
            (markers || []).forEach(function (marker) {
                boundsPoints.push(marker.point);
            });

            window.L.circleMarker(coordinates[0], {
                radius: 6,
                color: '#fff',
                weight: 2,
                fillColor: '#16a34a',
                fillOpacity: 1,
            }).addTo(map);
            window.L.circleMarker(coordinates[coordinates.length - 1], {
                radius: 6,
                color: '#fff',
                weight: 2,
                fillColor: '#0f172a',
                fillOpacity: 1,
            }).addTo(map);
            (markers || []).forEach(function (marker) {
                window.L.marker(marker.point, {
                    interactive: false,
                    icon: window.L.divIcon({
                        className: '',
                        html: '<div class="sr-custom-mini-pin" style="--pin-color:' + (marker.type === 'pickup' ? '#22c55e' : '#f97316') + '"></div>',
                        iconSize: [13, 13],
                        iconAnchor: [6, 6],
                    }),
                }).addTo(map);
            });
            map.fitBounds(window.L.latLngBounds(boundsPoints), { padding: [24, 24], animate: false });
            card.querySelector('.sr-thumb')?.classList.add('has-live-map');
            setTimeout(function () { map.invalidateSize(false); }, 80);
        }

        function customMarkers(card) {
            var raw = card.getAttribute('data-custom-points') || '[]';
            var items = [];
            try { items = JSON.parse(raw); } catch (error) { items = []; }
            return (Array.isArray(items) ? items : [])
                .map(function (item) {
                    var lat = toNumber(item.lat);
                    var lng = toNumber(item.lng);
                    if (lat === null || lng === null) return null;
                    return { type: item.type === 'pickup' ? 'pickup' : 'dropoff', point: [lat, lng] };
                })
                .filter(Boolean);
        }

        function initRouteCard(card) {
            var aLat = toNumber(card.dataset.pointALat);
            var aLng = toNumber(card.dataset.pointALng);
            var bLat = toNumber(card.dataset.pointBLat);
            var bLng = toNumber(card.dataset.pointBLng);
            if ([aLat, aLng, bLat, bLng].some(function (value) { return value === null; })) return Promise.resolve();

            var markers = customMarkers(card);
            var routePoints = [[aLat, aLng]]
                .concat(markers.map(function (marker) { return marker.point; }))
                .concat([[bLat, bLng]]);
            var url = 'https://router.project-osrm.org/route/v1/driving/'
                + routePoints.map(function (point) {
                    return encodeURIComponent(point[1] + ',' + point[0]);
                }).join(';')
                + '?overview=full&geometries=geojson';

            return fetch(url)
                .then(function (response) {
                    if (!response.ok) throw new Error('route');
                    return response.json();
                })
                .then(function (payload) {
                    var route = payload && payload.routes && payload.routes[0];
                    if (!route || !route.geometry || !Array.isArray(route.geometry.coordinates)) {
                        throw new Error('geometry');
                    }
                    updateRouteStats(card, Number(route.distance), Number(route.duration));
                    var coordinates = route.geometry.coordinates
                        .map(function (pair) { return [Number(pair[1]), Number(pair[0])]; })
                        .filter(function (pair) { return Number.isFinite(pair[0]) && Number.isFinite(pair[1]); });
                    renderMiniMap(card, coordinates.length ? coordinates : routePoints, markers);
                })
                .catch(function () {
                    renderMiniMap(card, fallbackRoute(card, routePoints), markers);
                });
        }

        var skel = document.getElementById('sr-skel-container');
        var real = document.getElementById('srGrid');

        function hideSkeleton() {
            if (skel && real) {
                setTimeout(function () {
                    skel.style.opacity = '0';
                    skel.style.pointerEvents = 'none';
                    real.style.opacity = '1';
                    setTimeout(function () {
                        skel.style.display = 'none';
                    }, 350);
                }, 280);
            }
        }

        if (cards.length > 0) {
            var promises = cards.map(initRouteCard);
            Promise.allSettled(promises).then(hideSkeleton);
        } else {
            hideSkeleton();
        }
    })();
