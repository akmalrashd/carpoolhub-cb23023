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

{{-- ── Floating Action Button ──────────────────────────────────────── --}}
<button id="ai-fab" onclick="aiChat.toggle()" aria-label="CarpoolHub AI" title="CarpoolHub AI">
    <i class="fa-solid fa-wand-magic-sparkles"></i>
</button>

{{-- ── Chat Window ──────────────────────────────────────────────────── --}}
<div id="ai-chat-window" aria-hidden="true">

    {{-- Header --}}
    <div class="ai-head">
        <div class="ai-head-left">
            <div class="ai-head-avatar">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
            </div>
            <div>
                <div class="ai-head-title">CarpoolHub AI</div>
                <div class="ai-head-sub" id="ai-head-sub">Your smart assistant</div>
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
        <textarea
            id="ai-input"
            class="ai-input"
            rows="1"
            maxlength="500"
            onkeydown="aiChat.onKey(event)"
            oninput="aiChat.resize(this)"
        ></textarea>
        <button type="submit" id="ai-send-btn" class="ai-send-btn">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </form>

</div>

{{-- ── Styles ───────────────────────────────────────────────────────── --}}
<style>
/* ── FAB ── */
#ai-fab {
    position: fixed;
    bottom: 88px;
    right: 16px;
    z-index: 3000;
    width: 52px;
    height: 52px;
    border-radius: 999px;
    background: var(--ch-yellow);
    border: none;
    box-shadow: var(--shadow-yellow), 0 4px 14px rgba(0,0,0,.12);
    color: var(--ch-yellow-ink);
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform .2s cubic-bezier(.34,1.56,.64,1), box-shadow .2s ease, background .15s;
}
#ai-fab:hover { transform: scale(1.1); background: var(--ch-yellow-deep); }
#ai-fab.is-open { background: var(--ink); color: #fff; box-shadow: var(--shadow-3); }
@media (min-width: 1024px) {
    #ai-fab { bottom: 32px; right: 32px; width: 56px; height: 56px; font-size: 22px; }
}

/* ── Window ── */
#ai-chat-window {
    position: fixed;
    bottom: 152px;
    right: 16px;
    z-index: 2990;
    width: min(360px, calc(100vw - 32px));
    max-height: min(520px, calc(100vh - 180px));
    background: var(--surface);
    border: 1px solid var(--hairline);
    border-radius: 20px;
    box-shadow: var(--shadow-3);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    opacity: 0;
    transform: translateY(16px) scale(.97);
    pointer-events: none;
    transition: opacity .2s ease, transform .22s cubic-bezier(.34,1.28,.64,1);
}
#ai-chat-window.is-open { opacity: 1; transform: translateY(0) scale(1); pointer-events: all; }
@media (min-width: 1024px) { #ai-chat-window { bottom: 104px; right: 32px; } }

/* ── Header ── */
.ai-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 12px 14px;
    border-bottom: 1px solid var(--hairline);
    background: var(--surface);
    flex-shrink: 0;
}
.ai-head-left { display: flex; align-items: center; gap: 10px; min-width: 0; }
.ai-head-avatar {
    width: 36px; height: 36px; border-radius: 999px;
    background: var(--ch-yellow); border: 1px solid var(--ch-yellow-line);
    color: var(--ch-yellow-ink); display: flex; align-items: center;
    justify-content: center; font-size: 15px; flex-shrink: 0;
}
.ai-head-title { font-size: 14px; font-weight: 800; color: var(--ink); font-family: var(--font-display); }
.ai-head-sub { font-size: 11px; color: var(--muted); font-weight: 600; margin-top: 1px; }
.ai-head-actions { display: flex; gap: 4px; flex-shrink: 0; }
.ai-icon-btn {
    width: 30px; height: 30px; border-radius: 8px;
    border: 1px solid var(--hairline-strong); background: var(--surface);
    color: var(--muted); font-size: 13px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .14s, color .14s;
}
.ai-icon-btn:hover { background: var(--surface-2); color: var(--ink); }

/* ── Extras (trip card, nav btn) — full width below bubble ── */
.ai-extras {
    width: 100%;
    animation: aiBubbleIn .2s ease-out both;
}

/* ── AI loading bar ── */
.ai-loading-bar {
    height: 2px;
    flex-shrink: 0;
    background: linear-gradient(90deg, var(--ch-yellow), #f59e0b, var(--ch-yellow));
    background-size: 200% 100%;
    opacity: 0;
    transition: opacity .15s;
    pointer-events: none;
}
.ai-loading-bar.active {
    opacity: 1;
    animation: aiBarSlide 1.1s linear infinite;
}
@keyframes aiBarSlide {
    0%   { background-position: 100% 0; }
    100% { background-position: -100% 0; }
}

/* ── Messages ── */
.ai-messages {
    flex: 1; overflow-y: auto; padding: 12px 14px;
    display: flex; flex-direction: column; gap: 8px;
    scroll-behavior: smooth; overscroll-behavior: contain;
}
.ai-messages::-webkit-scrollbar { width: 4px; }
.ai-messages::-webkit-scrollbar-thumb { background: var(--hairline-strong); border-radius: 99px; }

/* ── Language picker ── */
.ai-lang-picker {
    flex-shrink: 0;
    padding: 16px 14px 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    border-top: 1px solid var(--hairline);
    background: var(--surface);
    animation: aiBubbleIn .2s ease-out both;
}
.ai-lang-prompt {
    margin: 0;
    font-size: 12px;
    font-weight: 700;
    color: var(--muted);
    text-align: center;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.ai-lang-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.ai-lang-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 10px;
    border: 1.5px solid var(--hairline-strong);
    border-radius: 14px;
    background: var(--surface);
    cursor: pointer;
    transition: border-color .15s, background .15s, transform .15s;
}
.ai-lang-btn:hover {
    border-color: var(--ch-yellow-line);
    background: var(--ch-yellow-tint);
    transform: translateY(-2px);
}
.ai-lang-flag { font-size: 26px; line-height: 1; }
.ai-lang-name { font-size: 12px; font-weight: 800; color: var(--ink); }

/* ── Bubbles ── */
.ai-bubble { display: flex; max-width: 88%; animation: aiBubbleIn .18s ease-out both; }
@keyframes aiBubbleIn {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.ai-bubble-bot  { align-self: flex-start; }
.ai-bubble-user { align-self: flex-end; flex-direction: row-reverse; }
.ai-bubble-text {
    padding: 9px 13px; border-radius: 16px;
    font-size: 13px; line-height: 1.5; font-weight: 500; word-break: break-word;
}
.ai-bubble-bot .ai-bubble-text {
    background: var(--surface-2); border: 1px solid var(--hairline);
    color: var(--ink); border-bottom-left-radius: 5px;
}
.ai-bubble-user .ai-bubble-text {
    background: var(--ink); color: #fff; border-bottom-right-radius: 5px;
}

/* ── Typing ── */
.ai-typing .ai-bubble-text { background: var(--surface-2); border: 1px solid var(--hairline); padding: 10px 14px; }
.ai-typing-dots { display: inline-flex; gap: 4px; align-items: center; }
.ai-typing-dots span {
    width: 7px; height: 7px; border-radius: 999px; background: var(--muted-2);
    animation: aiDot .9s infinite ease-in-out;
}
.ai-typing-dots span:nth-child(2) { animation-delay: .15s; }
.ai-typing-dots span:nth-child(3) { animation-delay: .3s; }
@keyframes aiDot {
    0%,80%,100% { transform: scale(.7); opacity: .5; }
    40%          { transform: scale(1); opacity: 1; }
}

/* ── Trip draft card ── */
.ai-trip-card {
    margin-top: 0;
    border: 1.5px solid var(--ch-yellow-line);
    border-radius: 16px;
    background: linear-gradient(135deg, #fffdf4 0%, #fff8d6 100%);
    overflow: hidden;
    font-size: 12px;
}
.ai-trip-card-header {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 12px 8px;
    border-bottom: 1px solid var(--ch-yellow-line);
    background: rgba(250,204,21,.12);
}
.ai-trip-card-icon {
    width: 30px; height: 30px; border-radius: 8px;
    background: var(--ch-yellow); color: var(--ch-yellow-ink);
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; flex-shrink: 0;
}
.ai-trip-card-headtext { flex: 1; min-width: 0; }
.ai-trip-card-title { font-size: 12px; font-weight: 900; color: var(--ch-yellow-ink); letter-spacing: .02em; text-transform: uppercase; }
.ai-trip-card-subtitle { font-size: 11px; color: var(--warning); font-weight: 600; margin-top: 1px; }
.ai-trip-card-body { padding: 10px 12px; display: grid; gap: 7px; }
.ai-trip-card-datetime {
    display: flex; align-items: center; gap: 8px;
    background: var(--surface); border: 1px solid var(--hairline);
    border-radius: 10px; padding: 8px 10px;
}
.ai-trip-card-datetime i { color: var(--warning); font-size: 13px; flex-shrink: 0; }
.ai-trip-card-dt-text { display: grid; gap: 1px; }
.ai-trip-card-dt-date { font-size: 13px; font-weight: 800; color: var(--ink); line-height: 1.2; }
.ai-trip-card-dt-time { font-size: 11px; font-weight: 700; color: var(--muted); font-family: var(--font-mono); }
.ai-trip-card-pills { display: flex; gap: 6px; flex-wrap: wrap; }
.ai-trip-card-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: var(--surface); border: 1px solid var(--hairline);
    border-radius: var(--r-pill); padding: 4px 9px;
    font-size: 11px; font-weight: 700; color: var(--ink-3);
}
.ai-trip-card-pill i { font-size: 10px; color: var(--warning); }
.ai-trip-card-pill.public { border-color: #86efac; background: var(--success-soft); color: var(--success-ink); }
.ai-trip-card-pill.public i { color: var(--success); }
.ai-trip-card-pill.seats { border-color: #bfdbfe; background: var(--info-soft); color: var(--info-ink); }
.ai-trip-card-pill.seats i { color: var(--info); }
/* row item */
.ai-trip-card-row-item {
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px; font-size: 12px;
}
.ai-trip-card-row-item.ai-trip-row-warn .ai-trip-row-val { color: var(--warning); font-weight: 700; }
.ai-trip-row-label { color: var(--muted); font-weight: 700; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; }
.ai-trip-row-label i { color: var(--warning); font-size: 10px; }
.ai-trip-row-val { color: var(--ink); font-weight: 700; text-align: right; word-break: break-word; }

/* direction */
.ai-trip-card-direction {
    background: var(--surface); border: 1px solid var(--hairline);
    border-radius: 10px; padding: 8px 10px; display: grid; gap: 4px;
}
.ai-trip-dir-point { display: flex; align-items: center; gap: 8px; }
.ai-trip-dir-dot {
    width: 10px; height: 10px; border-radius: 999px; flex-shrink: 0;
    border: 2px solid var(--surface); box-shadow: 0 0 0 1.5px var(--hairline-strong);
}
.ai-trip-dir-dot.pickup { background: #22c55e; }
.ai-trip-dir-dot.dest   { background: #334155; }
.ai-trip-dir-name { font-size: 12px; font-weight: 700; color: var(--ink); }
.ai-trip-dir-line { padding-left: 4px; line-height: 1; }

.ai-trip-open-btn {
    display: flex; align-items: center; justify-content: center; gap: 7px;
    width: calc(100% - 24px);
    margin: 0 12px 12px;
    min-height: 38px; border-radius: 11px;
    background: var(--ink); color: #fff; border: none;
    font-size: 13px; font-weight: 800; cursor: pointer;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    box-sizing: border-box;
    transition: background .14s, transform .12s;
}
.ai-trip-open-btn:hover { background: #1e293b; transform: translateY(-1px); }
.ai-trip-open-btn i { font-size: 12px; flex-shrink: 0; }

/* ── No-route card ── */
.ai-no-route-card {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 12px; border-radius: 12px;
    background: var(--warning-soft); border: 1px solid rgba(180,83,9,.22);
    font-size: 12px;
}
.ai-no-route-icon { color: var(--warning); font-size: 15px; flex-shrink: 0; }
.ai-no-route-text { flex: 1; font-weight: 700; color: var(--warning-ink); }
.ai-no-route-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 11px; border-radius: 8px;
    background: var(--warning); color: #fff;
    font-size: 11px; font-weight: 800; text-decoration: none;
    white-space: nowrap; flex-shrink: 0;
    transition: opacity .14s;
}
.ai-no-route-btn:hover { opacity: .85; }

/* ── Navigate card ── */
.ai-nav-btn {
    margin-top: 8px; display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 13px; border-radius: 10px; background: var(--info-soft);
    color: var(--info-ink); border: 1px solid rgba(37,99,235,.18);
    font-size: 12px; font-weight: 800; text-decoration: none; cursor: pointer;
}
.ai-nav-btn:hover { background: #bfdbfe; }

/* ── Chips ── */
.ai-chips { display: flex; gap: 6px; flex-wrap: wrap; padding: 8px 14px 0; flex-shrink: 0; }
.ai-chip {
    border: 1px solid var(--hairline-strong); border-radius: var(--r-pill);
    background: var(--surface); color: var(--ink-2);
    padding: 5px 11px; font-size: 12px; font-weight: 700;
    cursor: pointer; white-space: nowrap;
    transition: background .14s, border-color .14s, color .14s;
}
.ai-chip:hover { background: var(--ch-yellow-tint); border-color: var(--ch-yellow-line); color: var(--ch-yellow-ink); }

/* ── Input ── */
.ai-input-row {
    display: flex; align-items: flex-end; gap: 8px;
    padding: 10px 14px 12px; border-top: 1px solid var(--hairline);
    background: var(--surface); flex-shrink: 0;
}
.ai-input {
    flex: 1; min-height: 38px; max-height: 96px;
    border-radius: 12px; border: 1px solid var(--hairline-strong);
    background: var(--surface-2); color: var(--ink);
    padding: 9px 12px; font-size: 13px; font-family: var(--font-ui);
    font-weight: 500; resize: none; outline: none;
    overflow-y: auto; line-height: 1.4;
    transition: border-color .15s, box-shadow .15s;
}
.ai-input:focus { border-color: var(--ch-yellow-line); box-shadow: 0 0 0 3px rgba(250,204,21,.18); }
.ai-input::placeholder { color: var(--muted-2); }
.ai-send-btn {
    width: 38px; height: 38px; border-radius: 12px; border: none;
    background: var(--ch-yellow); color: var(--ch-yellow-ink);
    font-size: 15px; cursor: pointer;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    transition: background .14s, transform .14s;
}
.ai-send-btn:hover { background: var(--ch-yellow-deep); transform: scale(1.05); }
.ai-send-btn:disabled { background: var(--surface-2); color: var(--muted-2); cursor: not-allowed; transform: none; }
</style>

{{-- ── Script ──────────────────────────────────────────────────────── --}}
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

    const WELCOME = {
        ms: `Hi <strong>${FIRST_NAME}</strong>! 👋 Apa yang boleh saya bantu hari ni?`,
        en: `Hi <strong>${FIRST_NAME}</strong>! 👋 What can I help you with today?`,
    };

    const PLACEHOLDER = { ms: 'Taip mesej...', en: 'Type a message...' };
    const HEADER_SUB  = { en: 'Your smart assistant', ms: 'Pembantu pintar anda' };

    // ── State ────────────────────────────────────────────────────────
    let isOpen     = false;
    let loading    = false;
    let lang       = localStorage.getItem(LANG_KEY) ?? null;
    let messageLog = []; // persisted to localStorage

    // ── DOM helpers ──────────────────────────────────────────────────
    const $ = id => document.getElementById(id);

    function toggle()   { isOpen ? close() : open(); }
    function close()    { isOpen = false; $('ai-fab').classList.remove('is-open'); $('ai-chat-window').classList.remove('is-open'); $('ai-chat-window').setAttribute('aria-hidden','true'); }

    function open() {
        isOpen = true;
        $('ai-fab').classList.add('is-open');
        $('ai-chat-window').classList.add('is-open');
        $('ai-chat-window').setAttribute('aria-hidden','false');
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
                extrasWrap.appendChild(buildNavCard(entry.url));
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
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
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

    function buildNavCard(url) {
        const label = (lang ?? DEFAULT_LANG) === 'ms' ? 'Pergi ke sana' : 'Take me there';
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

        try {
            const res  = await fetch(CHAT_URL, {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
                body: JSON.stringify({ message, language: lang ?? DEFAULT_LANG }),
            });
            const data = await res.json();
            removeTyping();
            barStop();

            if (data.intent === 'trip_draft' && data.data) {
                addBubble(data.reply, 'bot', buildTripCard(data.data), { type: 'trip_draft', data: data.data });
            } else if (data.intent === 'route_draft' && data.data) {
                addBubble(data.reply, 'bot', buildRouteDraftCard(data.data, data.url), { type: 'route_draft', data: data.data, url: data.url });
            } else if (data.intent === 'no_route' && data.route_url) {
                addBubble(data.reply, 'bot', buildNoRouteCard(data.route_url), { type: 'no_route', url: data.route_url, reply: data.reply });
            } else if (data.intent === 'navigate' && data.url) {
                addBubble(data.reply, 'bot', buildNavCard(data.url), { type: 'navigate', url: data.url });
            } else {
                addBubble(data.reply || ((lang ?? DEFAULT_LANG) === 'ms' ? 'Maaf, cuba lagi.' : 'Sorry, try again.'), 'bot');
            }
        } catch {
            removeTyping();
            removeTyping();
            barStop();
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
        const picker = $('ai-lang-picker');
        if (picker) picker.style.display = '';
        const sub = $('ai-head-sub');
        if (sub) sub.textContent = 'Your smart assistant';
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
        const win = $('ai-chat-window');
        const fab = $('ai-fab');
        if (win && !win.contains(e.target) && fab && !fab.contains(e.target)) {
            close();
        }
    });

    // ── Init ─────────────────────────────────────────────────────────
    if (lang) activateLang();

    return { toggle, open, close, send, sendQuick, onKey, resize, clear, setLang, openTripForm, openRouteForm };
})();
</script>
