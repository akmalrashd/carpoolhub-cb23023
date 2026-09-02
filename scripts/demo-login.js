// One-time helper: opens a browser, you log in manually (whichever driver
// account you want to star in the ad video), then press Enter here — it
// saves the session to scripts/auth.json so demo-record.js can reuse it and
// skip login entirely on every future run.
//
// This is dev tooling only (Playwright as a devDependency for these two
// scripts) — it doesn't add a build step to the app itself.
//
// Run:
//   npm run demo:login
//   (or) DEMO_BASE_URL=http://localhost/CarpoolHub-Laravel/public node scripts/demo-login.js

import { chromium } from 'playwright';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
// No Apache vhost is configured for this project (verified: http://localhost/
// itself 302s elsewhere), so it's served under XAMPP's default docroot path.
// Set up a vhost pointing at CarpoolHub-Laravel/public and pass DEMO_BASE_URL
// to use a clean domain instead.
const BASE_URL = process.env.DEMO_BASE_URL || 'http://localhost/CarpoolHub-Laravel/public';
const AUTH_STATE_PATH = path.join(__dirname, 'auth.json');

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({ viewport: { width: 480, height: 900 } });
    const page = await context.newPage();
    await page.goto(`${BASE_URL}/login`);

    console.log('\nLog in manually in the browser window that just opened.');
    console.log('Use the driver account you want the demo video to follow (it needs approved');
    console.log('driver verification so trip creation isn\'t blocked — see ensureDriverIsApprovedAndCurrent).');
    console.log('Once you land on the Home dashboard, come back to this terminal and press Enter...\n');

    await new Promise((resolve) => process.stdin.once('data', resolve));

    await context.storageState({ path: AUTH_STATE_PATH });
    console.log(`Saved login session to ${AUTH_STATE_PATH}`);
    console.log('demo-record.js will now pick this up automatically — no credentials needed.');

    await browser.close();
    process.exit(0);
})();
