{{--
    A soft, recurring nudge to connect Telegram — not a hard gate (unlike
    email verification), just a once-a-day reminder for anyone who hasn't
    connected yet. Shown from the shared layout so it can catch a user on
    any authenticated page, but the 24h cadence itself lives in localStorage
    (per-browser, not per-account) so it's checked client-side without a
    round trip or a DB column just to remember "when did we last nag them".
--}}
@php
    $telegramReady = ! empty(config('services.telegram.bot_token')) && ! empty(config('services.telegram.bot_username'));
@endphp

@if($telegramReady && ! auth()->user()->telegram_chat_id)
    <div class="tg-nudge-overlay" id="tgNudgeOverlay" aria-hidden="true">
        <div class="tg-nudge-card">
            <button type="button" class="tg-nudge-close" id="tgNudgeClose" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <span class="tg-nudge-icon"><i class="fa-brands fa-telegram"></i></span>

            <h3 class="tg-nudge-title">Connect Telegram</h3>
            <p class="tg-nudge-desc">
                Get instant alerts for trip updates, join requests, and
                payments — even when CarpoolHub is closed. Takes one tap.
            </p>

            <form method="POST" action="{{ route('telegram.link') }}" class="tg-nudge-form">
                @csrf
                <button type="submit" class="tg-nudge-primary-btn">
                    <i class="fa-brands fa-telegram"></i> Connect Telegram
                </button>
            </form>
            <button type="button" class="tg-nudge-later-btn" id="tgNudgeLater">Later</button>
        </div>
    </div>

    <script>
        (function () {
            var KEY = 'ch_tg_nudge_last_shown';
            var DAY_MS = 24 * 60 * 60 * 1000;
            var last = parseInt(localStorage.getItem(KEY) || '0', 10);
            var now = Date.now();

            if (last && (now - last) < DAY_MS) return;

            var overlay = document.getElementById('tgNudgeOverlay');
            if (!overlay) return;

            function dismiss() {
                overlay.classList.remove('show');
                overlay.setAttribute('aria-hidden', 'true');
            }

            overlay.classList.add('show');
            overlay.setAttribute('aria-hidden', 'false');
            localStorage.setItem(KEY, String(now));

            document.getElementById('tgNudgeClose').addEventListener('click', dismiss);
            document.getElementById('tgNudgeLater').addEventListener('click', dismiss);
            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) dismiss();
            });
        })();
    </script>
@endif
