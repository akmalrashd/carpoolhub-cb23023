// Settings > Notifications: enable/disable browser push. Deliberately
// button-triggered rather than auto-prompted on page load — an unprompted
// permission dialog is the single biggest cause of users blocking
// notifications forever and never being asked again.
(function () {
    const enableBtn = document.getElementById('pushEnableBtn');
    const disableBtn = document.getElementById('pushDisableBtn');
    const pill = document.getElementById('pushStatusPill');
    const desc = document.getElementById('pushStatusDesc');
    if (!enableBtn || !disableBtn || !pill) return;

    const defaultDesc = desc ? desc.textContent : '';
    const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

    function setPill(text, on) {
        pill.textContent = text;
        pill.classList.toggle('on', on);
        pill.classList.toggle('off', !on);
    }

    // Surfaced in the UI, not just the console — a silent console.error is
    // invisible to anyone who isn't a developer, which made a real failure
    // here look identical to "nothing happened" from the user's side.
    function showError(message) {
        if (desc) {
            desc.textContent = message;
            desc.style.color = 'var(--danger, #b91c1c)';
        }
    }

    function clearError() {
        if (desc) {
            desc.textContent = defaultDesc;
            desc.style.color = '';
        }
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(base64);
        const output = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; i++) output[i] = raw.charCodeAt(i);
        return output;
    }

    // Throws with the server's own message on failure instead of just
    // returning a boolean the caller has to remember to check — a rejected
    // subscribe (e.g. endpoint host not on the allowlist) used to fail
    // completely silently server-side.
    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.__csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(body || {}),
        });

        if (!res.ok) {
            let message = 'Server rejected the request (' + res.status + ').';
            try {
                const data = await res.json();
                if (data && data.message) message = data.message;
            } catch (e) { /* non-JSON error body — keep the generic message */ }
            throw new Error(message);
        }

        return res.json().catch(() => ({}));
    }

    async function refreshUi() {
        if (!supported) {
            setPill('Not supported', false);
            desc.textContent = 'Not supported on this browser/device. Try Telegram below instead.';
            enableBtn.hidden = true;
            disableBtn.hidden = true;
            return;
        }

        if (Notification.permission === 'denied') {
            setPill('Blocked', false);
            desc.textContent = 'Blocked in your browser settings. Allow them, then reload.';
            enableBtn.hidden = true;
            disableBtn.hidden = true;
            return;
        }

        // navigator.serviceWorker.ready only resolves once a worker is
        // actually controlling this scope — if registration ever fails (wrong
        // scope, sw.js 404s, browser policy) it never resolves at all, and
        // this used to leave the pill on its blade-default "Checking…" text
        // forever with no way out. Race it against a timeout so every path
        // reaches a real terminal state.
        let reg;
        try {
            reg = await Promise.race([
                navigator.serviceWorker.ready,
                new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 8000)),
            ]);
        } catch (e) {
            console.error('Service worker never became ready', e);
            setPill('Unavailable', false);
            desc.textContent = 'Could not set up push notifications on this page. Try Telegram below, or reload this page.';
            enableBtn.hidden = true;
            disableBtn.hidden = true;
            return;
        }

        const sub = await reg.pushManager.getSubscription();

        if (sub) {
            setPill('Enabled', true);
            enableBtn.hidden = true;
            disableBtn.hidden = false;
        } else {
            setPill('Disabled', false);
            enableBtn.hidden = false;
            disableBtn.hidden = true;
        }
    }

    enableBtn.addEventListener('click', async () => {
        enableBtn.disabled = true;
        clearError();
        try {
            if (!window.__vapidPublicKey) {
                throw new Error('Push isn’t configured on this server yet (missing VAPID key). Contact support.');
            }

            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                await refreshUi();
                return;
            }

            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(window.__vapidPublicKey),
            });

            await postJson('/push/subscribe', sub.toJSON());
        } catch (e) {
            console.error('Push subscribe failed', e);
            showError((e && e.message) ? e.message : 'Could not enable push notifications. Please try again.');
        } finally {
            enableBtn.disabled = false;
            await refreshUi();
        }
    });

    disableBtn.addEventListener('click', async () => {
        disableBtn.disabled = true;
        clearError();
        try {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            if (sub) {
                // Telling the server first, then always unsubscribing
                // client-side regardless of whether that call succeeded —
                // otherwise a server-side hiccup would leave a subscription
                // the UI can never turn back off.
                try {
                    await postJson('/push/unsubscribe', { endpoint: sub.endpoint });
                } catch (serverError) {
                    console.error('Push unsubscribe (server) failed', serverError);
                }
                await sub.unsubscribe();
            }
        } catch (e) {
            console.error('Push unsubscribe failed', e);
            showError((e && e.message) ? e.message : 'Could not disable push notifications. Please try again.');
        } finally {
            disableBtn.disabled = false;
            await refreshUi();
        }
    });

    refreshUi();
})();
