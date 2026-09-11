/**
 * Read-only browser regression audit.
 * First: STICKVERSE_VISUAL_EXPORT=1 php bin/phpunit --filter VisualIdentityControllerTest
 * Serve public/ and var/ui-audit/router.php on localhost:8791, then run this script.
 * Requires Playwright; CHROME_PATH can point to an existing Chrome executable.
 * Never uses production accounts or sends purchasing/combat/account forms.
 */
const { chromium } = require('playwright');
const fs = require('node:fs/promises');
const path = require('node:path');
const assert = require('node:assert/strict');

(async () => {
    const directory = path.resolve('var/ui-audit');
    const base = process.env.VISUAL_AUDIT_URL || 'http://127.0.0.1:8791';
    assert(['127.0.0.1', 'localhost'].includes(new URL(base).hostname), 'Local fixtures only');
    const browser = await chromium.launch({
        headless: true,
        ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}),
    });
    const errors = [], resources = [], results = [];
    const lobby = JSON.parse(await fs.readFile(path.join(directory, 'lobby-active.json'), 'utf8'));
    const battle = JSON.parse(await fs.readFile(path.join(directory, 'battle.json'), 'utf8'));
    const fixtures = (await fs.readdir(directory)).filter(name => name.endsWith('.html'));
    const page = await browser.newPage();
    page.on('pageerror', error => errors.push(String(error)));
    page.on('response', response => {
        if (response.status() >= 400 && /\/(assets|images)\//.test(response.url())) resources.push(response.url());
    });
    await page.route('**/salon-combat-en-ligne', route => route.fulfill({ json: lobby }));
    await page.route('**/combat-en-ligne/**', route => {
        assert.equal(route.request().method(), 'GET', 'No combat submission during visual audit');
        return route.fulfill({ json: battle });
    });
    async function open(name, width = 390) {
        await page.setViewportSize({ width, height: 950 });
        await page.goto(base + '/audit/' + name + '.html', { waitUntil: 'load' });
        await page.waitForSelector('.navigation-ready', { state: 'attached' }).catch(error => {
            console.error('Page initialization failed:', name, errors, resources);
            throw error;
        });
    }
    try {
        for (const width of [375, 390, 430, 768, 1024, 1440, 1920]) {
            for (const filename of fixtures) {
                await open(filename.slice(0, -5), width);
                if (filename === 'combat.html') await page.waitForSelector('.carte-combattant');
                const overflow = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth);
                results.push({ page: filename, width, overflow });
                assert(overflow <= 1, filename + ' overflows at ' + width + 'px: ' + overflow);
            }
        }
        await open('login');
        const menu = page.locator('[data-navigation-toggle]');
        await menu.click();
        assert.equal(await menu.getAttribute('aria-expanded'), 'true');
        await page.keyboard.press('Escape');
        assert.equal(await menu.getAttribute('aria-expanded'), 'false');
        await page.locator('#password').fill('visual-test-only');
        await page.locator('[data-password-visibility-toggle]').click();
        assert.equal(await page.locator('#password').getAttribute('type'), 'text');
        await page.locator('[data-password-visibility-toggle]').click();
        assert.equal(await page.locator('#password').getAttribute('type'), 'password');
        await page.locator('#remember_me').check();
        assert(await page.locator('#remember_me').isChecked());
        await open('wiki');
        await page.locator('[name="recherche"]').fill('no-card-exists-123');
        await page.waitForSelector('[data-catalog-empty]:not([hidden])');
        assert.equal(await page.locator('[data-catalog-card]:not([hidden])').count(), 0);
        await page.locator('button[type="reset"]').click();
        await page.waitForSelector('[data-catalog-card]:not([hidden])');
        await page.locator('[name="rarete"]').selectOption('5');
        assert(await page.locator('[data-catalog-card]:not([hidden])').evaluateAll(cards => cards.every(c => c.dataset.rarity === '5')));
        await page.locator('button[type="reset"]').click();
        await page.locator('[name="recherche"]').fill('esprit');
        await page.waitForSelector('[data-catalog-card]:not([hidden]) button[data-passif-viewer-trigger]');
        const triggers = page.locator('[data-catalog-card]:not([hidden]) button[data-passif-viewer-trigger]');
        await triggers.first().click();
        const popover = page.locator('.passif-viewer-popover');
        assert.equal(await popover.count(), 1);
        assert(await popover.evaluate(el => el.scrollHeight > el.clientHeight), 'Long descriptions must scroll');
        await triggers.nth(1).click();
        assert.equal(await popover.count(), 1, 'Only one passive at a time');
        await popover.click();
        assert.equal(await popover.count(), 0);
        assert((await page.url()).includes('/wiki.html'), 'Closing a passive must not open its card');
        await triggers.first().click();
        await page.keyboard.press('Escape');
        assert.equal(await triggers.first().getAttribute('aria-expanded'), 'false');
        await open('teams');
        await page.locator('.team-inventory-card:not(:disabled)').first().click();
        await page.waitForSelector('.team-slot.is-filled');
        assert.equal(await page.locator('.team-slot.is-filled').count(), 1);
        // Inspect the existing modal without opening/buying any crate.
        for (const width of [375, 390, 430, 768, 1024, 1440, 1920]) {
            await open('shop-player', width);
            await page.locator('.crate-opening-overlay').evaluate(el => {
                el.hidden = false;
                el.setAttribute('aria-hidden', 'false');
            });
            const dialog = await page.locator('.crate-opening-dialog').boundingBox();
            assert(dialog.x >= 0 && dialog.x + dialog.width <= width + 1, 'Crate modal must fit');
            await page.locator('.crate-opening-close').click();
            await page.waitForSelector('.crate-opening-overlay', { state: 'hidden' });
        }
        await open('combat', 390);
        await page.waitForSelector('.carte-combattant');
        assert.equal(await page.locator('.carte-combattant').count(), 8);
        // Open the existing combat passive without selecting the card.
        await page.locator('.carte-combattant-passif').first().click();
        assert.equal(await page.locator('.carte-combattant').count(), 8);
        await page.screenshot({path: path.join(directory, 'combat-mobile.png'), fullPage:true});
        await open('home', 1440);
        await page.screenshot({path: path.join(directory, 'home-desktop.png'), fullPage:true});
        await open('wiki', 390);
        await page.screenshot({path: path.join(directory, 'wiki-mobile.png'), fullPage:true});
        assert.deepEqual(errors, [], 'JavaScript console errors');
        assert.deepEqual([...new Set(resources)], [], 'Missing assets');
        await fs.writeFile(path.join(directory, 'responsive-report.json'), JSON.stringify({results, errors, resources, interactions:'passed'}, null, 2));
        console.log(JSON.stringify({checks:results.length, widths:7, interactions:'passed', javascriptErrors:errors.length, missingAssets:resources.length}));
    } finally {
        await browser.close();
    }
})().catch(error => {console.error(error);process.exitCode = 1;});
