@php
    $user        = auth()->user();
    $isDriver    = in_array($user?->role, ['driver', 'admin'], true);
    $csrfToken   = csrf_token();
    $chatUrl     = route('ai.chat');
    $clearUrl    = route('ai.chat.clear');
    $tripsCreate = route('trips.create');
    $firstName   = $user?->name ? explode(' ', $user->name)[0] : 'there';
    $role        = $user?->role ?? 'passenger';
@endphp

{{-- ── Chat Window ──────────────────────────────────────────────────── --}}
<div id="ai-chat-window" aria-hidden="true" style="opacity:0; pointer-events:none; display:none;">

    {{-- Header --}}
    <div class="ai-head">
        <div class="ai-head-left">
            <div class="ai-head-avatar">
                <x-mascot size="34" variant="yellow" state="idle" id="ai-chat-mascot" />
            </div>
            <div>
                <div class="ai-head-title">CarpoolHub AI</div>
                <div class="ai-head-sub" id="ai-head-sub">Hexa · your smart assistant</div>
            </div>
        </div>
        <div class="ai-head-actions">
            <button onclick="aiChat.clear()" title="Change language" class="ai-icon-btn" id="ai-lang-reset-btn" style="display:none">
                <img id="ai-lang-flag-img" src="" alt="" width="18" height="13" style="border-radius:2px;display:block;">
            </button>
            <button onclick="aiChat.clear()" title="Clear chat" class="ai-icon-btn" id="ai-clear-btn" style="display:none">
                <i class="fa-solid fa-rotate-left"></i>
            </button>
            <button onclick="aiChat.close()" class="ai-icon-btn">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    {{-- AI loading bar (below header, only shows during request) --}}
    <div id="ai-loading-bar" class="ai-loading-bar"></div>

    {{-- Messages --}}
    <div id="ai-messages" class="ai-messages"></div>

    {{-- Language Picker --}}
    <div id="ai-lang-picker" class="ai-lang-picker">
        <p class="ai-lang-prompt">Choose language / Pilih bahasa</p>
        <div class="ai-lang-options">
            <button class="ai-lang-btn" onclick="aiChat.setLang('en')">
                <img class="ai-lang-flag" src="https://flagcdn.com/w40/gb.png" alt="GB" width="32" height="24">
                <span class="ai-lang-name">English</span>
            </button>
            <button class="ai-lang-btn" onclick="aiChat.setLang('ms')">
                <img class="ai-lang-flag" src="https://flagcdn.com/w40/my.png" alt="MY" width="32" height="24">
                <span class="ai-lang-name">Bahasa Malaysia</span>
            </button>
        </div>
    </div>

    {{-- Quick chips (hidden until language selected) --}}
    <div class="ai-chips" id="ai-chips" style="display:none"></div>

    {{-- Input row (hidden until language selected) --}}
    <form id="ai-form" class="ai-input-row" onsubmit="aiChat.send(event)" style="display:none">
        <div class="ai-input-wrap">
            <textarea
                id="ai-input"
                class="ai-input"
                rows="1"
                maxlength="500"
                onkeydown="aiChat.onKey(event)"
                oninput="aiChat.resize(this)"
            ></textarea>
            <button
                type="button"
                id="ai-mic-btn"
                class="ai-mic-btn"
                onclick="aiChat.toggleVoice()"
                title="Voice Input (Speech-to-Text)"
                aria-label="Voice Input"
            >
                <i class="fa-solid fa-microphone"></i>
                <span class="ai-mic-pulse"></span>
            </button>
        </div>
        <button type="submit" id="ai-send-btn" class="ai-send-btn">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </form>

    <div class="ai-disclaimer" id="ai-disclaimer" style="display:none"></div>

</div>

{{-- ai-chat.css is loaded directly in layouts/app.blade.php's <head> instead —
     this component is rendered from that same layout's own body, later in the
     SAME top-to-bottom execution, so a @push('styles') called from here would
     run after <head>'s @stack('styles') has already been output. Too late to
     matter, unlike a page's own @push (that works — see any page view — because
     @extends fully evaluates the child view into a buffer before the layout
     renders at all). --}}

{{-- ── Script ──────────────────────────────────────────────────────── --}}
<script src="{{ asset('js/mascot.js') }}?v={{ filemtime(public_path('js/mascot.js')) }}"></script>
<script>
const aiChat = (() => {
    const CHAT_URL   = @json($chatUrl);
    const CLEAR_URL  = @json($clearUrl);
    const CSRF       = @json($csrfToken);
    const TRIPS_URL  = @json($tripsCreate);
    const ROLE       = @json($role);
    const FIRST_NAME = @json($firstName);
    const LANG_KEY     = 'ch_ai_lang';
    const MSG_KEY      = 'ch_ai_messages';
    const DEFAULT_LANG = 'en';
    const MAX_MSG_SAVE = 30; // max messages to keep in localStorage

    // ── Chip definitions (both languages) ───────────────────────────
    const CHIPS = {
        driver: {
            ms: [
                { label: '🚗 Buat Trip',    msg: 'Aku nak buat trip baru' },
                { label: '📋 Trip Saya',    msg: 'Tunjukkan trip saya' },
                { label: '💰 Bayaran',      msg: 'Tunjukkan status bayaran saya' },
                { label: '❓ Cara Guna',    msg: 'Macam mana nak guna CarpoolHub?' },
            ],
            en: [
                { label: '🚗 New Trip',     msg: 'I want to create a new trip' },
                { label: '📋 My Trips',     msg: 'Show my trips' },
                { label: '💰 Payments',     msg: 'Show my payment status' },
                { label: '❓ How to use',   msg: 'How do I use CarpoolHub?' },
            ],
        },
        passenger: {
            ms: [
                { label: '🔍 Cari Trip',    msg: 'Aku nak cari trip' },
                { label: '💳 Bayaran Saya', msg: 'Tunjukkan bayaran tertunggak saya' },
                { label: '🤝 Connections',  msg: 'Tunjukkan connections saya' },
                { label: '❓ Cara Guna',    msg: 'Macam mana nak guna CarpoolHub?' },
            ],
            en: [
                { label: '🔍 Find a Trip',  msg: 'I want to find a trip' },
                { label: '💳 My Payments',  msg: 'Show my outstanding payments' },
                { label: '🤝 Connections',  msg: 'Show my connections' },
                { label: '❓ How to use',   msg: 'How do I use CarpoolHub?' },
            ],
        },
        admin: {
            ms: [
                { label: '📊 Laporan',      msg: 'Bawa ke laporan admin' },
                { label: '👥 Pengguna',     msg: 'Bawa ke pengurusan pengguna' },
                { label: '🚗 Semua Trip',   msg: 'Tunjukkan semua trip' },
                { label: '❓ Cara Guna',    msg: 'Macam mana nak guna CarpoolHub?' },
            ],
            en: [
                { label: '📊 Reports',      msg: 'Go to admin reports' },
                { label: '👥 Manage Users', msg: 'Go to user management' },
                { label: '🚗 All Trips',    msg: 'Show all trips' },
                { label: '❓ How to use',   msg: 'How do I use CarpoolHub?' },
            ],
        },
    };

    // Escaped because WELCOME is handed to addBubbleHtml(), which assigns it via
    // innerHTML — a display name containing markup would otherwise execute.
    const WELCOME = {
        ms: `Hi <strong>${escHtml(FIRST_NAME)}</strong>! 👋 Saya Hexa — apa yang boleh saya bantu hari ni?`,
        en: `Hi <strong>${escHtml(FIRST_NAME)}</strong>! 👋 I'm Hexa — what can I help you with today?`,
    };

    const PLACEHOLDER = { ms: 'Taip mesej...', en: 'Type a message...' };
    const HEADER_SUB  = { en: 'Hexa · your smart assistant', ms: 'Hexa · pembantu pintar anda' };
    const DISCLAIMER  = {
        en: 'Hexa is AI and can make mistakes. Please double-check responses.',
        ms: 'Hexa kadang-kadang boleh tersilap. Sila semak balik maklumat penting.',
    };

    // ── State ────────────────────────────────────────────────────────
    let isOpen     = false;
    let loading    = false;
    let lang       = localStorage.getItem(LANG_KEY) ?? null;
    let messageLog = []; // persisted to localStorage

    // ── DOM helpers ──────────────────────────────────────────────────
    const $ = id => document.getElementById(id);

    // Header AI button renders once per header variant (desktop topbar +
    // mobile header), both present in the DOM at once — drive both mascots
    // together so whichever is visible reacts.
    const FAB_MASCOT_IDS = ['ai-fab-mascot-desktop', 'ai-fab-mascot-mobile'];
    function fabMascot(fn, ...args) { FAB_MASCOT_IDS.forEach(id => Mascot?.[fn]?.(id, ...args)); }

    function toggle(e)   { if (e) e.stopPropagation(); isOpen ? close() : open(); }
    function close()    { isOpen = false; document.querySelectorAll('#ai-fab').forEach(el => el.classList.remove('is-open')); $('ai-chat-window').classList.remove('is-open'); $('ai-chat-window').setAttribute('aria-hidden','true'); }

    function open() {
        isOpen = true;
        // The header's other dropdowns (profile/notifications/bento menu) are
        // native <details> elements with their own separate mutual-exclusion
        // logic in app.blade.php — this button isn't one of them, so opening
        // the AI panel has to close them itself.
        document.querySelectorAll('.notification-wrap, .profile-wrap, .more-menu, .bento-menu-wrap').forEach(function (detail) {
            detail.removeAttribute('open');
        });
        document.querySelectorAll('#ai-fab').forEach(el => el.classList.add('is-open'));
        $('ai-chat-window').classList.add('is-open');
        $('ai-chat-window').setAttribute('aria-hidden','false');
        Mascot?.play('ai-chat-mascot', 'wink', { duration: 900 });
        fabMascot('play', 'wink', { duration: 900 });
        scrollBottom();
        if (lang) setTimeout(() => $('ai-input').focus(), 220);
    }

    // ── Language selection ───────────────────────────────────────────
    function setLang(l) {
        lang = l;
        localStorage.setItem(LANG_KEY, l);
        activateLang();
    }

    function activateLang() {
        // Hide picker, show input + chips
        const picker = $('ai-lang-picker');
        if (picker) picker.style.display = 'none';

        $('ai-form').style.display           = '';
        $('ai-clear-btn').style.display      = '';
        $('ai-lang-reset-btn').style.display = '';
        const disclaimer = $('ai-disclaimer');
        if (disclaimer) {
            disclaimer.textContent = DISCLAIMER[lang] ?? DISCLAIMER.ms;
            disclaimer.style.display = '';
        }

        // Update flag image to reflect current language
        const flagImg = document.getElementById('ai-lang-flag-img');
        if (flagImg) {
            flagImg.src = lang === 'ms'
                ? 'https://flagcdn.com/w40/my.png'
                : 'https://flagcdn.com/w40/gb.png';
            flagImg.alt = lang === 'ms' ? 'MY' : 'GB';
        }

        // Update header sub & input placeholder
        const sub = $('ai-head-sub');
        if (sub) sub.textContent = HEADER_SUB[lang] ?? HEADER_SUB.ms;
        const inp = $('ai-input');
        if (inp) inp.placeholder = PLACEHOLDER[lang] ?? PLACEHOLDER.ms;

        // Restore saved messages or show welcome
        const m = $('ai-messages');
        if (m && m.children.length === 0) {
            const restored = restoreMessages();
            if (!restored) {
                addBubbleHtml(WELCOME[lang] ?? WELCOME.ms, 'bot');
            }
        }

        // Render chips
        renderChips();

        scrollBottom();
        setTimeout(() => $('ai-input')?.focus(), 100);
    }

    function renderChips() {
        const container = $('ai-chips');
        if (!container) return;
        const roleKey = ['driver','admin'].includes(ROLE) ? (ROLE === 'admin' ? 'admin' : 'driver') : 'passenger';
        const set = CHIPS[roleKey]?.[lang] ?? CHIPS[roleKey]?.ms ?? [];

        container.innerHTML = '';
        set.forEach(chip => {
            const btn = document.createElement('button');
            btn.className = 'ai-chip';
            btn.textContent = chip.label;
            btn.onclick = () => sendQuick(chip.msg);
            container.appendChild(btn);
        });
        container.style.display = 'flex';
    }

    // ── Message persistence ───────────────────────────────────────────
    function saveMsgLog() {
        try {
            localStorage.setItem(MSG_KEY, JSON.stringify(messageLog.slice(-MAX_MSG_SAVE)));
        } catch {}
    }

    function loadMsgLog() {
        try {
            const raw = localStorage.getItem(MSG_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch { return []; }
    }

    function restoreMessages() {
        const saved = loadMsgLog();
        if (!saved.length) return false;

        messageLog = saved;
        const m = $('ai-messages');
        if (!m) return false;

        saved.forEach(entry => {
            if (entry.type === 'bubble') {
                const wrap = document.createElement('div');
                wrap.className = `ai-bubble ai-bubble-${entry.side}`;
                const bubble = document.createElement('div');
                bubble.className = 'ai-bubble-text';
                bubble.innerHTML = entry.html;
                wrap.appendChild(bubble);
                m.appendChild(wrap);
            } else if (entry.type === 'trip_draft' && entry.data) {
                const extrasWrap = document.createElement('div');
                extrasWrap.className = 'ai-extras';
                extrasWrap.appendChild(buildTripCard(entry.data));
                m.appendChild(extrasWrap);
            } else if (entry.type === 'route_draft' && entry.data) {
                const extrasWrap = document.createElement('div');
                extrasWrap.className = 'ai-extras';
                extrasWrap.appendChild(buildRouteDraftCard(entry.data, entry.url));
                m.appendChild(extrasWrap);
            } else if (entry.type === 'no_route' && entry.url) {
                const extrasWrap = document.createElement('div');
                extrasWrap.className = 'ai-extras';
                extrasWrap.appendChild(buildNoRouteCard(entry.url));
                m.appendChild(extrasWrap);
            } else if (entry.type === 'navigate' && entry.url) {
                const extrasWrap = document.createElement('div');
                extrasWrap.className = 'ai-extras';
                extrasWrap.appendChild(buildNavCard(entry.url, entry.route));
                m.appendChild(extrasWrap);
            }
        });

        return saved.length > 0;
    }

    // ── Messages ─────────────────────────────────────────────────────
    function scrollBottom() { const m = $('ai-messages'); if (m) m.scrollTop = m.scrollHeight; }

    function addBubble(text, side, extras = null, extrasMeta = null) {
        const m = $('ai-messages');

        const wrap   = document.createElement('div');
        wrap.className = `ai-bubble ai-bubble-${side}`;
        const bubble = document.createElement('div');
        bubble.className = 'ai-bubble-text';
        const html = renderMd(text);
        bubble.innerHTML = html;
        wrap.appendChild(bubble);
        m.appendChild(wrap);

        // Log bubble for persistence
        messageLog.push({ type: 'bubble', side, html });

        // extras (trip card, nav btn) render full-width below the bubble
        if (extras) {
            const extrasWrap = document.createElement('div');
            extrasWrap.className = 'ai-extras';
            extrasWrap.appendChild(extras);
            m.appendChild(extrasWrap);

            // Log extras for persistence
            if (extrasMeta) messageLog.push(extrasMeta);
        }

        saveMsgLog();
        scrollBottom();
        return wrap;
    }

    function addBubbleHtml(html, side, persist = true) {
        const wrap = document.createElement('div');
        wrap.className = `ai-bubble ai-bubble-${side}`;
        const bubble = document.createElement('div');
        bubble.className = 'ai-bubble-text';
        bubble.innerHTML = html;
        wrap.appendChild(bubble);
        $('ai-messages').appendChild(wrap);
        if (persist) { messageLog.push({ type: 'bubble', side, html }); saveMsgLog(); }
        scrollBottom();
    }

    function addTyping() {
        const wrap = document.createElement('div');
        wrap.className = 'ai-bubble ai-bubble-bot ai-typing';
        wrap.id = 'ai-typing';
        wrap.innerHTML = '<div class="ai-bubble-text"><div class="ai-typing-dots"><span></span><span></span><span></span></div></div>';
        $('ai-messages').appendChild(wrap);
        scrollBottom();
    }

    function removeTyping() { const el = $('ai-typing'); if (el) el.remove(); }

    function hideChips() { const c = $('ai-chips'); if (c) c.style.display = 'none'; }

    // ── Markdown ─────────────────────────────────────────────────────
    function escHtml(str) {
        // The apostrophe matters: escHtml() output is interpolated into the
        // single-quoted onclick="" attribute of the trip card (openTripForm),
        // so an unescaped ' breaks out of the attribute. Escaped entities decode
        // back to the same character on render, so display is unchanged.
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
    function renderMd(str) {
        return escHtml(str)
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g,     '<em>$1</em>')
            .replace(/\n/g, '<br>');
    }

    // ── Trip/Nav cards ───────────────────────────────────────────────
    function buildTripCard(data) {
        const isMalay = lang === 'ms';
        const L = isMalay
            ? { draft:'DRAF TRIP', autofill:'Auto-fill dari AI', two:'Dua hala', one:'Satu hala',
                pub:'Awam', priv:'Peribadi', nodt:'Tarikh belum ditetapkan', noroute:'Route belum dipilih',
                btn:'Buka Form Trip', seats:' tempat', route:'Route', dir:'Arah', passengers:'Penumpang',
                nopass:'Tiada penumpang' }
            : { draft:'TRIP DRAFT', autofill:'Auto-filled by AI', two:'Round trip', one:'One-way',
                pub:'Public', priv:'Private', nodt:'Date not set', noroute:'Route not selected',
                btn:'Open Trip Form', seats:' seats', route:'Route', dir:'Direction', passengers:'Passengers',
                nopass:'No passengers' };

        // Format datetime
        let dtDate = L.nodt, dtTime = '';
        if (data.trip_datetime) {
            const d = new Date(data.trip_datetime.replace(' ', 'T'));
            if (!isNaN(d)) {
                dtDate = d.toLocaleDateString('en-GB', { weekday:'short', day:'numeric', month:'short', year:'numeric' });
                dtTime = d.toLocaleTimeString('en-GB', { hour:'2-digit', minute:'2-digit' });
            }
        }

        const isPublic = data.visibility === 'public';
        const isTwoWay = data.trip_type === 'two_way';
        const seats    = data.seat_limit ? `${data.seat_limit}${L.seats}` : '—';
        const safeData = escHtml(JSON.stringify(data));

        // Route & direction
        const routeName   = data.route_name || L.noroute;
        const pickupName  = data.pickup_name || '—';
        const destName    = data.destination_name || '—';
        const hasRoute    = Boolean(data.route_name);

        // Passengers — prefer names, fallback to count from IDs
        const names   = Array.isArray(data.participant_names) ? data.participant_names.filter(Boolean) : [];
        const ids     = Array.isArray(data.participant_ids)   ? data.participant_ids.filter(Boolean)   : [];
        const hasPassengers = names.length > 0 || ids.length > 0;
        const passengerDisplay = names.length > 0
            ? names.map(escHtml).join(', ')
            : (ids.length > 0 ? ids.length + (isMalay ? ' penumpang dipilih' : ' passenger(s) selected') : null);

        const card = document.createElement('div');
        card.className = 'ai-trip-card';
        card.innerHTML = `
            <div class="ai-trip-card-header">
                <div class="ai-trip-card-icon"><i class="fa-solid fa-car-side"></i></div>
                <div class="ai-trip-card-headtext">
                    <div class="ai-trip-card-title">${L.draft}</div>
                    <div class="ai-trip-card-subtitle">${L.autofill}</div>
                </div>
            </div>
            <div class="ai-trip-card-body">

                {{-- Route row --}}
                <div class="ai-trip-card-row-item ${hasRoute ? '' : 'ai-trip-row-warn'}">
                    <span class="ai-trip-row-label"><i class="fa-solid fa-route"></i> ${L.route}</span>
                    <span class="ai-trip-row-val">${escHtml(routeName)}</span>
                </div>

                {{-- Direction row --}}
                <div class="ai-trip-card-direction">
                    <div class="ai-trip-dir-point">
                        <span class="ai-trip-dir-dot pickup"></span>
                        <span class="ai-trip-dir-name">${escHtml(pickupName)}</span>
                    </div>
                    <div class="ai-trip-dir-line"><i class="fa-solid fa-arrow-down" style="font-size:8px;color:var(--muted-2)"></i></div>
                    <div class="ai-trip-dir-point">
                        <span class="ai-trip-dir-dot dest"></span>
                        <span class="ai-trip-dir-name">${escHtml(destName)}</span>
                    </div>
                </div>

                {{-- Datetime --}}
                <div class="ai-trip-card-datetime">
                    <i class="fa-regular fa-calendar"></i>
                    <div class="ai-trip-card-dt-text">
                        <span class="ai-trip-card-dt-date">${escHtml(dtDate)}</span>
                        ${dtTime ? `<span class="ai-trip-card-dt-time">${escHtml(dtTime)}</span>` : ''}
                    </div>
                </div>

                {{-- Pills --}}
                <div class="ai-trip-card-pills">
                    <span class="ai-trip-card-pill">
                        <i class="fa-solid fa-${isTwoWay ? 'repeat' : 'arrow-right'}"></i>
                        ${isTwoWay ? L.two : L.one}
                    </span>
                    <span class="ai-trip-card-pill ${isPublic ? 'public' : ''}">
                        <i class="fa-solid fa-${isPublic ? 'globe' : 'lock'}"></i>
                        ${isPublic ? L.pub : L.priv}
                    </span>
                    <span class="ai-trip-card-pill seats">
                        <i class="fa-solid fa-chair"></i> ${escHtml(seats)}
                    </span>
                </div>

                {{-- Passengers --}}
                <div class="ai-trip-card-row-item">
                    <span class="ai-trip-row-label"><i class="fa-solid fa-users"></i> ${L.passengers}</span>
                    <span class="ai-trip-row-val">${hasPassengers ? passengerDisplay : `<span style="color:var(--muted)">${L.nopass}</span>`}</span>
                </div>

            </div>
            <button class="ai-trip-open-btn" onclick='aiChat.openTripForm(${safeData})'>
                <i class="fa-solid fa-arrow-up-right-from-square"></i> ${L.btn}
            </button>`;
        return card;
    }

    const NAV_LABELS = {
        'trips.index':         { ms: 'Lihat Senarai Trip', en: 'View My Trips' },
        'trips.create':        { ms: 'Buka Borang Trip',   en: 'Open Trip Form' },
        'payments.index':      { ms: 'Lihat Bayaran',      en: 'View Payments' },
        'explore.index':       { ms: 'Cari Trip',          en: 'Find a Ride' },
        'connections.index':   { ms: 'Lihat Connections',  en: 'View Connections' },
        'saved-routes.index':  { ms: 'Lihat Saved Route',  en: 'View Saved Routes' },
        'settings.index':      { ms: 'Buka Settings',      en: 'Open Settings' },
        'notifications.index': { ms: 'Lihat Notification', en: 'View Notifications' },
    };

    function buildNavCard(url, route) {
        const isMalay = (lang ?? DEFAULT_LANG) === 'ms';
        const fallback = isMalay ? 'Pergi ke sana' : 'Take me there';
        const label = (NAV_LABELS[route] && NAV_LABELS[route][isMalay ? 'ms' : 'en']) || fallback;
        const a = document.createElement('a');
        a.className = 'ai-nav-btn';
        a.href = url;
        a.innerHTML = `<i class="fa-solid fa-arrow-right"></i> ${label}`;
        return a;
    }

    function buildRouteDraftCard(data, url) {
        const isMalay = (lang ?? DEFAULT_LANG) === 'ms';
        const safeData = escHtml(JSON.stringify(data));
        const L = isMalay
            ? { title:'DRAF SAVED ROUTE', sub:'Koordinat dianggar oleh AI', from:'Dari', to:'Ke',
                fare:'Tambang', dist:'Jarak', btn:'Buka Form Route', approx:'(anggaran)' }
            : { title:'SAVED ROUTE DRAFT', sub:'Coordinates estimated by AI', from:'From', to:'To',
                fare:'Fare', dist:'Distance', btn:'Open Route Form', approx:'(approx)' };

        const div = document.createElement('div');
        div.className = 'ai-trip-card'; // same card shell as trip draft
        div.innerHTML = `
            <div class="ai-trip-card-header">
                <div class="ai-trip-card-icon"><i class="fa-solid fa-route"></i></div>
                <div class="ai-trip-card-headtext">
                    <div class="ai-trip-card-title">${L.title}</div>
                    <div class="ai-trip-card-subtitle">${L.sub}</div>
                </div>
            </div>
            <div class="ai-trip-card-body">
                <div class="ai-trip-card-direction">
                    <div class="ai-trip-dir-point">
                        <span class="ai-trip-dir-dot pickup"></span>
                        <div style="min-width:0">
                            <div class="ai-trip-dir-name">${escHtml(data.point_a_name || '—')}</div>
                            <div style="font-size:10px;color:var(--muted);font-family:var(--font-mono)">
                                ${data.point_a_lat ? `${(+data.point_a_lat).toFixed(5)}, ${(+data.point_a_lng).toFixed(5)}` : '—'} <span style="color:var(--muted-2)">${L.approx}</span>
                            </div>
                        </div>
                    </div>
                    <div class="ai-trip-dir-line"><i class="fa-solid fa-arrow-down" style="font-size:8px;color:var(--muted-2)"></i></div>
                    <div class="ai-trip-dir-point">
                        <span class="ai-trip-dir-dot dest"></span>
                        <div style="min-width:0">
                            <div class="ai-trip-dir-name">${escHtml(data.point_b_name || '—')}</div>
                            <div style="font-size:10px;color:var(--muted);font-family:var(--font-mono)">
                                ${data.point_b_lat ? `${(+data.point_b_lat).toFixed(5)}, ${(+data.point_b_lng).toFixed(5)}` : '—'} <span style="color:var(--muted-2)">${L.approx}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ai-trip-card-pills">
                    ${data.distance_km ? `<span class="ai-trip-card-pill"><i class="fa-solid fa-road"></i> ~${(+data.distance_km).toFixed(0)} km</span>` : ''}
                    ${data.default_fare ? `<span class="ai-trip-card-pill public"><i class="fa-solid fa-tag"></i> RM ${(+data.default_fare).toFixed(2)}</span>` : ''}
                </div>
            </div>
            <button class="ai-trip-open-btn" onclick='aiChat.openRouteForm(${safeData})'>
                <i class="fa-solid fa-arrow-up-right-from-square"></i> ${L.btn}
            </button>`;
        return div;
    }

    function buildNoRouteCard(url) {
        const isMalay = (lang ?? DEFAULT_LANG) === 'ms';
        const label  = isMalay ? 'Tambah Saved Route' : 'Add Saved Route';
        const div = document.createElement('div');
        div.className = 'ai-no-route-card';
        div.innerHTML = `
            <div class="ai-no-route-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="ai-no-route-text">
                <span>${isMalay ? 'Tiada route yang sepadan' : 'No matching route found'}</span>
            </div>
            <a href="${escHtml(url)}" class="ai-no-route-btn">
                <i class="fa-solid fa-plus"></i> ${label}
            </a>`;
        return div;
    }

    // ── Loading bar helpers ───────────────────────────────────────────
    function barStart() {
        $('ai-loading-bar')?.classList.add('active');
        // Suppress global page-load-line while AI is busy
        const g = document.getElementById('pageLoadLine');
        if (g) g.style.display = 'none';
    }
    function barStop() {
        $('ai-loading-bar')?.classList.remove('active');
        const g = document.getElementById('pageLoadLine');
        if (g) g.style.display = '';
    }

    // ── Send ─────────────────────────────────────────────────────────
    async function sendMessage(message) {
        if (loading || !message.trim()) return;
        loading = true;
        $('ai-send-btn').disabled = true;
        hideChips();
        addBubble(message, 'user');
        addTyping();
        barStart();
        Mascot?.setState('ai-chat-mascot', 'thinking');
        fabMascot('setState', 'thinking');

        try {
            const res  = await fetch(CHAT_URL, {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
                body: JSON.stringify({ message, language: lang ?? DEFAULT_LANG }),
            });
            const data = await res.json();
            removeTyping();
            barStop();
            Mascot?.play('ai-chat-mascot', 'wink', { duration: 700 });
            fabMascot('play', 'wink', { duration: 700 });

            if (data.intent === 'trip_draft' && data.data) {
                addBubble(data.reply, 'bot', buildTripCard(data.data), { type: 'trip_draft', data: data.data });
            } else if (data.intent === 'route_draft' && data.data) {
                addBubble(data.reply, 'bot', buildRouteDraftCard(data.data, data.url), { type: 'route_draft', data: data.data, url: data.url });
            } else if (data.intent === 'no_route' && data.route_url) {
                addBubble(data.reply, 'bot', buildNoRouteCard(data.route_url), { type: 'no_route', url: data.route_url, reply: data.reply });
            } else if (data.intent === 'navigate' && data.url) {
                addBubble(data.reply, 'bot', buildNavCard(data.url, data.route), { type: 'navigate', url: data.url, route: data.route });
            } else {
                addBubble(data.reply || ((lang ?? DEFAULT_LANG) === 'ms' ? 'Maaf, cuba lagi.' : 'Sorry, try again.'), 'bot');
            }
        } catch {
            removeTyping();
            removeTyping();
            barStop();
            Mascot?.play('ai-chat-mascot', 'alert', { duration: 1800 });
            fabMascot('play', 'alert', { duration: 1800 });
            addBubble((lang ?? DEFAULT_LANG) === 'ms' ? 'Ralat rangkaian. Sila cuba lagi.' : 'Network error. Please try again.', 'bot');
        }

        loading = false;
        $('ai-send-btn').disabled = false;
    }

    function send(e) {
        e.preventDefault();
        const inp = $('ai-input');
        const msg = inp.value.trim();
        if (!msg || loading) return;
        inp.value = '';
        inp.style.height = '';
        sendMessage(msg);
    }

    function sendQuick(msg) { if (!loading) sendMessage(msg); }

    function onKey(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            const inp = $('ai-input');
            const msg = inp.value.trim();
            if (!msg || loading) return;
            inp.value = ''; inp.style.height = '';
            sendMessage(msg);
        }
    }

    function resize(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 96) + 'px'; }

    async function clear() {
        const msg = (lang ?? DEFAULT_LANG) === 'ms' ? 'Reset perbualan dan bahasa?' : 'Reset chat and language?';
        if (!confirm(msg)) return;
        await fetch(CLEAR_URL, { method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'} });

        // Reset language + messages
        lang = null;
        messageLog = [];
        localStorage.removeItem(LANG_KEY);
        localStorage.removeItem(MSG_KEY);

        // Reset UI
        $('ai-messages').innerHTML = '';
        $('ai-chips').innerHTML = '';
        $('ai-chips').style.display = 'none';
        $('ai-form').style.display = 'none';
        $('ai-clear-btn').style.display = 'none';
        $('ai-lang-reset-btn').style.display = 'none';
        $('ai-disclaimer').style.display = 'none';
        const picker = $('ai-lang-picker');
        if (picker) picker.style.display = '';
        const sub = $('ai-head-sub');
        if (sub) sub.textContent = 'Hexa · your smart assistant';
    }

    function openTripForm(data) {
        sessionStorage.setItem('ch_ai_trip_draft', JSON.stringify(data));
        window.location.href = TRIPS_URL;
    }

    function openRouteForm(data) {
        sessionStorage.setItem('ch_ai_route_draft', JSON.stringify(data));
        window.location.href = @json(route('saved-routes.create'));
    }

    // ── Click outside to close ───────────────────────────────────────
    document.addEventListener('click', function (e) {
        if (!isOpen) return;
        if (e.target.closest('#ai-fab') || e.target.closest('#ai-chat-window')) return;
        close();
    });

    // ── Voice Input (Speech-to-Text) ──────────────────────────────────
    let recognition = null;
    let isListening = false;
    let initialText = '';
    let silenceTimer = null;

    function resetSilenceTimer() {
        if (silenceTimer) clearTimeout(silenceTimer);
        silenceTimer = setTimeout(() => {
            if (isListening) {
                stopVoice();
            }
        }, 5000); // Auto-stop listening after 5 seconds of continuous silence
    }

    function toggleVoice() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            alert(lang === 'ms' 
                ? 'Pengesahan suara tidak disokong oleh pelayar anda. Sila guna Chrome, Edge, atau Safari.' 
                : 'Speech recognition is not supported by your browser. Please use Chrome, Edge, or Safari.');
            return;
        }

        if (isListening) {
            stopVoice();
        } else {
            startVoice(SpeechRecognition);
        }
    }

    function startVoice(SpeechRecognition) {
        try {
            const inp = $('ai-input');
            initialText = inp ? (inp.value ? inp.value.trim() + ' ' : '') : '';

            recognition = new SpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.lang = lang === 'ms' ? 'ms-MY' : 'en-US';

            const micBtn = $('ai-mic-btn');

            recognition.onstart = () => {
                isListening = true;
                if (micBtn) {
                    micBtn.classList.add('is-listening');
                    micBtn.title = lang === 'ms' ? 'Mendengar... (Klik untuk hentikan)' : 'Listening... (Click to stop)';
                }
                resetSilenceTimer();
            };

            recognition.onresult = (event) => {
                let transcript = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    transcript += event.results[i][0].transcript;
                }
                if (inp) {
                    inp.value = initialText + transcript;
                    resize(inp);
                }
                resetSilenceTimer();
            };

            recognition.onerror = (event) => {
                console.warn('Speech recognition error:', event.error);
                if (event.error === 'no-speech') return; // Keep listening on silent pauses
                stopVoice();
            };

            recognition.onend = () => {
                // If user hasn't manually clicked to stop, keep listening continuously
                if (isListening) {
                    try {
                        const currentInp = $('ai-input');
                        initialText = currentInp ? (currentInp.value ? currentInp.value.trim() + ' ' : '') : '';
                        recognition.start();
                        return;
                    } catch {}
                }
                stopVoice();
            };

            recognition.start();
        } catch (err) {
            console.error(err);
            stopVoice();
        }
    }

    function stopVoice() {
        isListening = false;
        if (silenceTimer) {
            clearTimeout(silenceTimer);
            silenceTimer = null;
        }
        if (recognition) {
            try { recognition.stop(); } catch {}
            recognition = null;
        }
        const micBtn = $('ai-mic-btn');
        if (micBtn) {
            micBtn.classList.remove('is-listening');
            micBtn.title = lang === 'ms' ? 'Input Suara (Speech-to-Text)' : 'Voice Input (Speech-to-Text)';
        }
    }

    // ── Gaze: look down at the input while it's focused ────────────────
    $('ai-input')?.addEventListener('focus', () => {
        const m = $('ai-chat-mascot');
        if (m && m.dataset.state !== 'thinking') Mascot?.setState('ai-chat-mascot', 'typing');
    });
    $('ai-input')?.addEventListener('blur', () => {
        const m = $('ai-chat-mascot');
        if (m && m.dataset.state === 'typing') Mascot?.setState('ai-chat-mascot', 'idle');
    });

    // ── Init ─────────────────────────────────────────────────────────
    if (lang) activateLang();

    return { toggle, open, close, send, sendQuick, onKey, resize, clear, setLang, openTripForm, openRouteForm, toggleVoice };
})();
</script>
