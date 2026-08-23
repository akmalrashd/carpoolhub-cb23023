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

    const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

    function setPill(text, on) {
        pill.textContent = text;
        pill.classList.toggle('on', on);
        pill.classList.toggle('off', !on);
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(base64);
        const output = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; i++) output[i] = raw.charCodeAt(i);
        return output;
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.__csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(body || {}),
        });
        return res.ok;
    }

    async function refreshUi() {
        if (!supported) {
            setPill('Not supported', false);
            desc.textContent = 'This browser (or this device — e.g. iPhone Safari not added to Home Screen) doesn’t support push notifications. Try Telegram below instead for reliable alerts.';
            enableBtn.hidden = true;
            disableBtn.hidden = true;
            return;
        }

        if (Notification.permission === 'denied') {
            setPill('Blocked', false);
            desc.textContent = 'Notifications are blocked for this site in your browser settings. Allow them there, then reload this page.';
            enableBtn.hidden = true;
            disableBtn.hidden = true;
            return;
        }

        const reg = await navigator.serviceWorker.ready;
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
        try {
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
        } finally {
            enableBtn.disabled = false;
            await refreshUi();
        }
    });

    disableBtn.addEventListener('click', async () => {
        disableBtn.disabled = true;
        try {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            if (sub) {
                await postJson('/push/unsubscribe', { endpoint: sub.endpoint });
                await sub.unsubscribe();
            }
        } catch (e) {
            console.error('Push unsubscribe failed', e);
        } finally {
            disableBtn.disabled = false;
            await refreshUi();
        }
    });

    refreshUi();
})();
