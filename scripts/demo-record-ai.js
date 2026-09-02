// Auto-plays a focused deep-dive on CarpoolHub's two AI features: Hexa (the
// chat assistant — general Q&A, natural-language navigation with filters,
// and drafting a whole trip from a plain-English request) and the Saved
// Route AI (automatic multi-route comparison + real fuel/toll fare advice
// baked into the Saved Route form). Six modular shots, console timing
// markers for narration sync later — same pattern as demo-record.js, just
// aimed at the AI surface specifically instead of the whole app.
//
// This is dev tooling only (Playwright as a devDependency) — it doesn't add
// a build step to the app itself.
//
// Every shot here makes at least one REAL Anthropic API call (this app has
// no mock/stub mode), several make two or three, and shot 5's route
// comparison fans out one call per route option plus a final cross-option
// call. Expect real cost and real wall-clock wait — the app's own UI warns
// "may take up to 20s" per call, and this script waits for the actual
// response rather than guessing a fixed delay. A full run easily takes
// several minutes, not the ~75s sum of the shots' target durations.
//
// Setup: same as demo-record.js (npm install, npx playwright install
// chromium, node scripts/demo-login.js once). Reuses the same
// scripts/auth.json session — no separate login.
//
// Run:
//   DEMO_BASE_URL=https://carpoolhub.prsdntworldwide.com node scripts/demo-record-ai.js
//
// Output: scripts/recordings/ai/*.webm, at the iPhone 14 Pro's native
// resolution (kept in a subfolder so it doesn't mix with demo-record.js's
// output in scripts/recordings/).
//
// Shot 4 (create a trip by chat) submits and PUBLISHES a real trip on
// whatever account/server this points at — same as demo-record.js's shot 4,
// confirmed acceptable there. Shot 4 needs this account to have a saved
// route whose point_a/point_b text plausibly matches the message below
// ("Fakulti Komputeran" — adjust FAKULTI_MESSAGE if this account's saved
// routes don't include that destination). Shot 5 needs at least one
// existing saved route to edit.

import { chromium, devices } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const BASE_URL = process.env.DEMO_BASE_URL || 'http://localhost/CarpoolHub-Laravel/public';
const AUTH_STATE_PATH = path.join(__dirname, 'auth.json');
const HAS_SAVED_SESSION = fs.existsSync(AUTH_STATE_PATH);

if (!HAS_SAVED_SESSION) {
    console.error(
        'No saved session found at scripts/auth.json.\n' +
        'Run `node scripts/demo-login.js` once, log in by hand, and press Enter there — ' +
        'this script will pick it up automatically on every run after that.'
    );
    process.exit(1);
}

// Matched against a real saved route destination at the time this was
// written ("UMPSA Pekan - Fakulti Komputeran, UMPSA Pekan") — change this if
// the account you're recording with has different saved routes.
const FAKULTI_MESSAGE = 'I want to create a trip to Fakulti Komputeran tomorrow at 10am, need 3 seats';

const t0 = Date.now();
function mark(label) {
    console.log(`[${((Date.now() - t0) / 1000).toFixed(1)}s] ${label}`);
}

// Same glassy, dotted-ring cursor overlay as demo-record.js — kept identical
// on purpose so footage from both scripts cuts together consistently.
const CURSOR_INIT_SCRIPT = `
(() => {
  const cursor = document.createElement('div');
  cursor.id = '__demo_cursor__';
  Object.assign(cursor.style, {
    position: 'fixed', top: '0', left: '0', width: '30px', height: '30px',
    borderRadius: '50%',
    background: 'rgba(255,255,255,0.16)',
    border: '1.5px dashed rgba(255,255,255,0.95)',
    boxShadow: '0 0 0 1px rgba(0,0,0,0.25), 0 6px 16px rgba(0,0,0,0.30), inset 0 0 8px rgba(255,255,255,0.45)',
    backdropFilter: 'blur(3px) saturate(160%)',
    WebkitBackdropFilter: 'blur(3px) saturate(160%)',
    pointerEvents: 'none', zIndex: '2147483647', transform: 'translate(-50%, -50%)',
    transition: 'transform 80ms ease-out',
  });
  const mount = () => { if (!cursor.isConnected && document.body) document.body.appendChild(cursor); };
  document.addEventListener('DOMContentLoaded', mount);
  mount();
  window.addEventListener('mousemove', (e) => {
    mount();
    cursor.style.left = e.clientX + 'px';
    cursor.style.top = e.clientY + 'px';
  }, true);
  window.addEventListener('mousedown', (e) => {
    cursor.style.transform = 'translate(-50%, -50%) scale(0.65)';
    const ripple = document.createElement('div');
    Object.assign(ripple.style, {
      position: 'fixed', left: e.clientX + 'px', top: e.clientY + 'px',
      width: '10px', height: '10px', marginLeft: '-5px', marginTop: '-5px',
      borderRadius: '50%', background: 'transparent',
      border: '1.5px dashed rgba(255,255,255,0.85)',
      boxShadow: '0 0 0 1px rgba(0,0,0,0.15)',
      pointerEvents: 'none', zIndex: '2147483646', transition: 'all 480ms ease-out',
    });
    document.body.appendChild(ripple);
    requestAnimationFrame(() => {
      ripple.style.width = '54px'; ripple.style.height = '54px';
      ripple.style.marginLeft = '-27px'; ripple.style.marginTop = '-27px';
      ripple.style.opacity = '0';
    });
    setTimeout(() => ripple.remove(), 520);
  }, true);
  window.addEventListener('mouseup', () => {
    cursor.style.transform = 'translate(-50%, -50%) scale(1)';
  }, true);
})();
`;

async function smoothClick(page, locator) {
    await locator.scrollIntoViewIfNeeded();
    const box = await locator.boundingBox();
    if (!box) throw new Error('smoothClick: element has no bounding box (not visible?)');
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2, { steps: 25 });
    await page.waitForTimeout(150);
    // force:true — see demo-record.js: a plain click proved unreliable
    // against this app's live host even when the element position is
    // already confirmed correct.
    await locator.click({ force: true });
}

async function openHexa(page) {
    await smoothClick(page, page.locator('.mobile-header #ai-fab'));
    await page.waitForSelector('#ai-chat-window[aria-hidden="false"]', { timeout: 8000 }).catch(() => { });
    await page.waitForTimeout(400);
    const englishBtn = page.locator('.ai-lang-picker').getByRole('button', { name: 'English' });
    if (await englishBtn.isVisible().catch(() => false)) {
        await smoothClick(page, englishBtn);
        await page.waitForTimeout(400);
    }
}

async function sendChatMessage(page, text) {
    const input = page.locator('#ai-input');
    await smoothClick(page, input);
    await input.pressSequentially(text, { delay: 30 });
    await page.waitForTimeout(400);
    await smoothClick(page, page.locator('#ai-send-btn'));
}

// Real Anthropic call — wait for the typing indicator to appear then
// disappear, rather than guessing a fixed delay.
async function waitForAiReply(page, timeout = 30000) {
    await page.waitForSelector('#ai-typing', { timeout: 5000 }).catch(() => { });
    await page.waitForSelector('#ai-typing', { state: 'detached', timeout }).catch(() => { });
}

async function shot1HexaIntro(page) {
    mark('SHOT 1/6 — Hexa intro (target 6s)');
    await page.goto(`${BASE_URL}/home`);
    await page.waitForLoadState('domcontentloaded');
    await page.waitForSelector('.hp-mobile-hero-title', { timeout: 15000 }).catch(() => { });
    await page.waitForTimeout(1000);

    await openHexa(page);
    await page.waitForTimeout(2500); // let the welcome bubble + quick chips settle on screen
}

async function shot2GeneralQA(page) {
    mark('SHOT 2/6 — General Q&A (target 10s)');
    const howToChip = page.locator('#ai-chips').getByRole('button', { name: /How to use/i });
    const hasChip = await howToChip.waitFor({ timeout: 5000 }).then(() => true).catch(() => false);
    if (hasChip) {
        await smoothClick(page, howToChip);
    } else {
        await sendChatMessage(page, 'How do I use CarpoolHub?');
    }
    await waitForAiReply(page);
    await page.waitForTimeout(2500);
}

async function shot3SmartNavigation(page) {
    mark('SHOT 3/6 — Smart navigation with filters (target 12s)');
    await sendChatMessage(page, 'Show me my unpaid payments');
    await waitForAiReply(page, 30000);
    await page.waitForTimeout(1000);

    // .last() in case an earlier shot's bot bubble also produced a nav card —
    // the chat log accumulates across the whole session.
    const navBtn = page.locator('.ai-nav-btn').last();
    if (await navBtn.count() > 0) {
        await smoothClick(page, navBtn);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForSelector('.payments-h1', { timeout: 15000 }).catch(() => { });
        await page.waitForTimeout(2000);
    } else {
        mark('  (Hexa replied without a navigate card this time — showing the reply only)');
        await page.waitForTimeout(2000);
    }
}

async function shot4CreateTripByChat(page) {
    mark('SHOT 4/6 — Create a trip entirely by chat (target 22s)');
    // Shot 3 navigated to a new page, which closes/resets the chat overlay.
    await openHexa(page);

    await sendChatMessage(page, FAKULTI_MESSAGE);
    await waitForAiReply(page, 30000);
    await page.waitForTimeout(1500);

    const tripCardBtn = page.locator('.ai-trip-open-btn').last();
    if (await tripCardBtn.count() === 0) {
        mark('  (Hexa could not match a saved route this time — skipping the publish step)');
        await page.waitForTimeout(2000);
        return;
    }

    await smoothClick(page, tripCardBtn);
    await page.waitForLoadState('domcontentloaded');
    // trips-create.js auto-jumps the wizard straight to Review once every
    // field from the draft is filled in — nothing to click through.
    await page.waitForSelector('#aiPrefillBanner:not([hidden])', { timeout: 15000 }).catch(() => { });
    await page.waitForSelector('.trip-publish-btn', { timeout: 15000 }).catch(() => { });
    await page.waitForTimeout(3000); // let the pre-filled Review screen actually read on camera

    const publishBtn = page.locator('.trip-publish-btn');
    if (await publishBtn.isVisible().catch(() => false)) {
        await smoothClick(page, publishBtn);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(2500);
    }
}

async function shot5SavedRouteAI(page) {
    mark('SHOT 5/6 — Saved Route AI: comparison & fare advisor (target 20s)');
    await page.goto(`${BASE_URL}/saved-routes`);
    await page.waitForLoadState('domcontentloaded');
    await page.waitForSelector('.sr-edit-btn', { timeout: 15000 }).catch(() => { });
    await page.waitForTimeout(1000);

    const editBtn = page.locator('.sr-edit-btn').first();
    if (await editBtn.count() === 0) {
        mark('  (no saved routes to edit — skipping)');
        await page.waitForTimeout(1500);
        return;
    }
    await smoothClick(page, editBtn);
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(1500);

    // Both points are already set on an existing route, so
    // fetchRouteOptions() fires automatically on load — nothing to click.
    await page.waitForSelector('#routeOptions .rf-route-option-btn', { timeout: 20000 }).catch(() => { });
    await page.waitForTimeout(2000); // let "AI is analyzing route..." actually show on camera

    // One real Anthropic call per route option, then a cross-option
    // comparison call — wait for the real "AI recommends ..." text instead
    // of guessing a fixed delay, matching the UI's own "may take up to 20s"
    // per-option warning.
    await page.waitForFunction(
        () => document.getElementById('routeRecommendation')?.textContent?.includes('recommends'),
        { timeout: 35000 }
    ).catch(() => { });
    await page.waitForTimeout(3500);
}

async function shot6Closing(page) {
    mark('SHOT 6/6 — Closing (target 5s)');
    await page.goto(`${BASE_URL}/home`);
    await page.waitForLoadState('domcontentloaded');
    await page.waitForSelector('.hp-mobile-hero-title', { timeout: 15000 }).catch(() => { });
    await page.waitForTimeout(3500);
}

(async () => {
    const device = devices['iPhone 14 Pro'];
    // Same reasoning as demo-record.js: not spreading the full device
    // descriptor (isMobile/hasTouch) — this app's mobile layout is a pure
    // CSS width breakpoint, and touch emulation made clicks unreliable on
    // the live host without changing how anything actually looks.
    const browser = await chromium.launch({ headless: false, slowMo: 35 });
    const context = await browser.newContext({
        userAgent: device.userAgent,
        viewport: { width: device.viewport.width, height: device.screen.height },
        deviceScaleFactor: device.deviceScaleFactor,
        isMobile: false,
        hasTouch: false,
        recordVideo: { dir: path.join(__dirname, 'recordings', 'ai'), size: { width: device.viewport.width, height: device.screen.height } },
        storageState: AUTH_STATE_PATH,
        permissions: ['geolocation'],
        geolocation: { latitude: 3.4934, longitude: 103.4267 },
    });
    context.setDefaultTimeout(25000);
    await context.addInitScript(CURSOR_INIT_SCRIPT);
    const page = await context.newPage();

    const debugDir = path.join(__dirname, 'recordings', 'ai', 'debug');
    fs.mkdirSync(debugDir, { recursive: true });

    async function runShot(name, fn) {
        try {
            await fn(page);
        } catch (err) {
            const shotPath = path.join(debugDir, `${name}-${Date.now()}.png`);
            await page.screenshot({ path: shotPath }).catch(() => { });
            mark(`  SHOT FAILED (skipping to next): ${err.message.split('\n')[0]}`);
            mark(`  → screenshot: scripts/recordings/ai/debug/${path.basename(shotPath)}`);
        }
    }

    mark('Using saved login session (scripts/auth.json)');
    await runShot('shot1-hexa-intro', shot1HexaIntro);
    await runShot('shot2-general-qa', shot2GeneralQA);
    await runShot('shot3-smart-navigation', shot3SmartNavigation);
    await runShot('shot4-create-trip-by-chat', shot4CreateTripByChat);
    await runShot('shot5-saved-route-ai', shot5SavedRouteAI);
    await runShot('shot6-closing', shot6Closing);
    mark('All 6 shots attempted');

    await context.close(); // finalizes the .webm file
    await browser.close();
    process.exit(0);
})();
