// Auto-plays a CarpoolHub product-video walkthrough — no manual clicking,
// just watch. Ten modular shots covering the app's sell points (search a
// ride, join it, post a ride, manage requests, get paid, the AI assistant),
// each recorded at native iPhone 14 Pro resolution via Playwright's built-in
// video capture. Add voice-over afterward in CapCut against the console
// timing markers this script prints as it runs.
//
// This is dev tooling only (Playwright as a devDependency for this script
// and demo-login.js) — it doesn't add a build step to the app itself.
//
// Setup (one-time):
//   npm install
//   npx playwright install chromium
//   node scripts/demo-login.js   — log in once as the driver account you
//                                   want the video to follow; the session is
//                                   reused automatically after that.
//
// Before recording: make sure that account has approved driver verification
// (see ensureDriverIsApprovedAndCurrent in TripController) or SHOT 4 (create
// a trip) will 403. Also make sure your local server is actually running.
//
// Run:
//   npm run demo:record
//   (or) DEMO_BASE_URL=http://localhost/CarpoolHub-Laravel/public node scripts/demo-record.js
//
// Output: scripts/recordings/*.webm, at the iPhone 14 Pro's native
// resolution. Import straight into CapCut, or convert first with:
//   ffmpeg -i input.webm -c:v libx264 -crf 18 output.mp4
//
// Shots 3-6 submit real requests (join a trip, publish a trip, approve a
// join request, mark a payment paid) so the recording shows genuine success
// states rather than a mockup. That means re-running this script leaves real
// rows behind in the dev database each time — expected, not a bug. Shots
// that depend on a *specific* pending item (approve a join request, mark a
// payment paid) check first and skip cleanly with a console note once
// there's nothing left to act on.

import { chromium, devices } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// No Apache vhost is configured for this project (verified: http://localhost/
// itself 302s elsewhere), so it's served under XAMPP's default docroot path.
// Set up a vhost pointing at CarpoolHub-Laravel/public and pass DEMO_BASE_URL
// to use a clean domain instead.
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

const t0 = Date.now();
function mark(label) {
    console.log(`[${((Date.now() - t0) / 1000).toFixed(1)}s] ${label}`);
}

// Injects a visible cursor + click-ripple so the recording shows what's being
// tapped (Playwright drives the real input pipeline but doesn't render an OS
// cursor by default). Glassmorphism look — translucent frosted circle with a
// dashed ring, no solid fill — so it reads as a UI affordance rather than a
// paint blob, and stays legible against both light and dark screens in the app.
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
    await locator.click();
}

// The driver's bottom tab bar (mobile-bottom-nav.blade.php) is role-aware and
// has no ids — scope by the nav landmark and accessible name instead of
// guessing hrefs.
function navLink(page, name) {
    return page.locator('.mobile-bottom-nav').getByRole('link', { name, exact: true });
}

async function shot1Home(page) {
    mark('SHOT 1/10 — Home dashboard (target 5s)');
    await page.goto(`${BASE_URL}/home`);
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('.hp-mobile-hero-title');
    await page.waitForTimeout(4500);
}

async function shot2Explore(page) {
    mark('SHOT 2/10 — Explore & Grab-style search (target 16s)');
    await smoothClick(page, navLink(page, 'Explore'));
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(800);

    // Tap the search pill — a real <a> to the dedicated search page
    // (Grab-style home screen: one tappable bar, not an inline filter form).
    await smoothClick(page, page.locator('.xp-search-pill'));
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('#search_destination');
    await page.waitForTimeout(500);

    // Type a real place name to show the live location search (debounced,
    // hits OpenStreetMap Nominatim) — a visual beat only, not depended on
    // for the actual results below.
    await smoothClick(page, page.locator('#search_destination'));
    await page.locator('#search_destination').pressSequentially('Fakulti Komputeran', { delay: 90 });
    await page.waitForTimeout(1800);

    // Clear it and pick a real "Suggested" destination instead (pulled from
    // actual trip data via exploreDestinationSuggestions), which guarantees
    // the results page below isn't empty.
    await page.locator('#search_destination').fill('');
    await smoothClick(page, page.locator('.xs2-tab[data-panel="suggested"]'));
    await page.waitForTimeout(400);
    const suggested = page.locator('.xs2-results[data-panel="suggested"] .xs2-result-row').first();
    if (await suggested.count() > 0) {
        await smoothClick(page, suggested);
        await page.waitForTimeout(400);
    }

    // Quick peek at the full-screen map picker, then back out without
    // pinning — shows the feature off without depending on live reverse
    // geocoding finishing in time.
    await smoothClick(page, page.locator('#openMapPickerBtn'));
    await page.waitForTimeout(1800);
    await smoothClick(page, page.locator('#closeMapPickerBtn'));
    await page.waitForTimeout(300);

    await smoothClick(page, page.locator('.xs2-submit-btn'));
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2500); // explore/index.blade.php refreshes its own list via AJAX on load — let it settle
}

async function shot3TripDetailsJoin(page) {
    mark('SHOT 3/10 — Trip details & request a seat (target 10s)');
    await page.waitForSelector('.open-explore-card', { timeout: 15000 });
    await smoothClick(page, page.locator('.open-explore-card').first());
    await page.waitForSelector('#exploreTripModal.is-open');
    await page.waitForTimeout(2200);

    const joinBtn = page.locator('#exploreModalJoinButton');
    const alreadyActedOn = await joinBtn.isDisabled().catch(() => true);
    if (!alreadyActedOn) {
        await smoothClick(page, joinBtn);
        await page.waitForSelector('#exploreModalSuccessOverlay:not([hidden])', { timeout: 10000 }).catch(() => { });
        await page.waitForTimeout(2200); // success overlay auto-closes the modal ~1.8s after it appears
    } else {
        mark('  (seat already requested on a previous run — showing details only)');
        await page.waitForTimeout(1500);
        await smoothClick(page, page.locator('#exploreTripModalClose'));
    }
}

async function shot4CreateTrip(page) {
    mark('SHOT 4/10 — Create a trip from a Saved Route (target 15s)');
    await smoothClick(page, navLink(page, 'Create trip'));
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('#savedRouteTrigger');
    await page.waitForTimeout(600);

    // Step 1 · Saved Route — picking one auto-fills pickup/destination/fare.
    await smoothClick(page, page.locator('#savedRouteTrigger'));
    await page.waitForSelector('.route-picker-option', { timeout: 10000 });
    await page.waitForTimeout(400);
    await smoothClick(page, page.locator('.route-picker-option').first());
    await page.waitForTimeout(900);
    await smoothClick(page, page.locator('[data-wizard-next="1"]'));
    await page.waitForTimeout(600);

    // Step 2 · Schedule & Capacity — push departure a few days out (public
    // trips must be strictly later than "now"; the field's own default of
    // exactly now() would fail that check by the time we submit) and flip to
    // Public so it actually lands on Explore.
    const future = new Date(Date.now() + 3 * 24 * 60 * 60 * 1000);
    future.setHours(10, 0, 0, 0);
    const pad = (n) => String(n).padStart(2, '0');
    const futureLocal = `${future.getFullYear()}-${pad(future.getMonth() + 1)}-${pad(future.getDate())}T${pad(future.getHours())}:${pad(future.getMinutes())}`;
    await page.locator('#trip_datetime').fill(futureLocal);
    await page.waitForTimeout(400);
    await smoothClick(page, page.locator('#visibility_public'));
    await page.waitForTimeout(500);
    const seatLimit = page.locator('#seat_limit');
    if (await seatLimit.isVisible().catch(() => false)) {
        await seatLimit.fill('3');
    }
    await page.waitForTimeout(500);
    await smoothClick(page, page.locator('[data-wizard-next="2"]'));
    await page.waitForTimeout(600);

    // Step 3 · Invite Passengers — optional, skip straight through.
    await smoothClick(page, page.locator('[data-wizard-next="3"]'));
    await page.waitForTimeout(800);

    // Step 4 · Fare — fully auto-computed from the saved route, nothing to fill.
    await smoothClick(page, page.locator('[data-wizard-next="4"]'));
    await page.waitForTimeout(1200);

    // Step 5 · Review & Publish.
    await smoothClick(page, page.locator('.trip-publish-btn'));
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2500);
}

async function shot5ManageRequests(page) {
    mark('SHOT 5/10 — Approve a join request (target 8s)');
    await smoothClick(page, navLink(page, 'Trips'));
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(600);

    // Only trip cards with a pending count show the badge — target one of those.
    const requestsBtn = page.locator('.open-trip-requests-review:has(.trip-request-badge)').first();
    if (await requestsBtn.count() === 0) {
        mark('  (no pending join requests left to approve — skipping)');
        await page.waitForTimeout(1500);
        return;
    }
    await smoothClick(page, requestsBtn);
    await page.waitForSelector('.open-trip-request-approve', { timeout: 8000 }).catch(() => { });
    await page.waitForTimeout(1200);

    const approveBtn = page.locator('.open-trip-request-approve').first();
    if (await approveBtn.count() > 0) {
        await smoothClick(page, approveBtn);
        await page.waitForTimeout(2000);
    }
    await page.waitForTimeout(1000);
}

async function shot6Payments(page) {
    mark('SHOT 6/10 — Payments ledger (target 10s)');
    await smoothClick(page, navLink(page, 'Payments'));
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2500); // let the RM summary + transaction list render

    const markPaidBtn = page.locator('.open-mark-paid-modal').first();
    if (await markPaidBtn.count() > 0) {
        await smoothClick(page, markPaidBtn);
        await page.waitForSelector('#markPaidModalMethod', { state: 'visible', timeout: 5000 }).catch(() => { });
        await page.waitForTimeout(800);
        await page.locator('#markPaidModalMethod').selectOption('cash');
        await page.waitForTimeout(500);
        await smoothClick(page, page.locator('.mark-paid-submit-btn'));
        await page.waitForTimeout(2000);
    } else {
        mark('  (no unpaid record to mark — skipping)');
        await page.waitForTimeout(1500);
    }
}

async function shot7Connections(page) {
    mark('SHOT 7/10 — Connections (target 8s)');
    await page.goto(`${BASE_URL}/connections`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1800);

    const acceptBtn = page.locator('.btn-action-accept').first();
    if (await acceptBtn.count() > 0) {
        await smoothClick(page, acceptBtn);
        await page.waitForTimeout(2000);
    } else {
        await page.waitForTimeout(1500);
    }
}

async function shot8AiChat(page) {
    mark('SHOT 8/10 — Hexa, the AI assistant (target 13s)');
    await smoothClick(page, page.locator('#ai-fab'));
    await page.waitForSelector('#ai-chat-window[aria-hidden="false"]', { timeout: 5000 }).catch(() => { });
    await page.waitForTimeout(600);

    const englishBtn = page.locator('.ai-lang-picker').getByRole('button', { name: 'English' });
    if (await englishBtn.isVisible().catch(() => false)) {
        await smoothClick(page, englishBtn);
        await page.waitForTimeout(500);
    }

    const howToChip = page.locator('#ai-chips').getByRole('button', { name: /How to use/i });
    await howToChip.waitFor({ timeout: 5000 }).catch(() => { });
    if (await howToChip.count() > 0) {
        await smoothClick(page, howToChip);
        // Real Anthropic API call — wait for the typing indicator to appear
        // then disappear, rather than guessing a fixed delay.
        await page.waitForSelector('#ai-typing', { timeout: 5000 }).catch(() => { });
        await page.waitForSelector('#ai-typing', { state: 'detached', timeout: 30000 }).catch(() => { });
        await page.waitForTimeout(1500);
    }
    await page.waitForTimeout(1000);
}

async function shot9Notifications(page) {
    mark('SHOT 9/10 — Notifications (target 5s)');
    await page.goto(`${BASE_URL}/notifications`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(4000);
}

async function shot10Closing(page) {
    mark('SHOT 10/10 — Closing (target 5s)');
    await page.goto(`${BASE_URL}/home`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(4000);
}

(async () => {
    const device = devices['iPhone 14 Pro'];
    const browser = await chromium.launch({ headless: false, slowMo: 35 });
    const context = await browser.newContext({
        ...device,
        recordVideo: { dir: path.join(__dirname, 'recordings'), size: device.viewport },
        storageState: AUTH_STATE_PATH,
        // Keeps "use my current location" (search page's locate-me button,
        // map picker) from hanging on a permission prompt mid-recording.
        // Coordinates are UMPSA Pekan, matching this app's real seed data.
        permissions: ['geolocation'],
        geolocation: { latitude: 3.4934, longitude: 103.4267 },
    });
    await context.addInitScript(CURSOR_INIT_SCRIPT);
    const page = await context.newPage();

    try {
        mark('Using saved login session (scripts/auth.json)');
        await shot1Home(page);
        await shot2Explore(page);
        await shot3TripDetailsJoin(page);
        await shot4CreateTrip(page);
        await shot5ManageRequests(page);
        await shot6Payments(page);
        await shot7Connections(page);
        await shot8AiChat(page);
        await shot9Notifications(page);
        await shot10Closing(page);
        mark('All 10 shots done');
    } catch (err) {
        mark(`FAILED: ${err.message}`);
        throw err;
    } finally {
        await context.close(); // finalizes the .webm file
        await browser.close();
        process.exit(0);
    }
})();
