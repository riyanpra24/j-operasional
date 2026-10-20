const { chromium } = require('playwright');
const path = require('path');
const assert = require('assert');

(async () => {
    const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
    try {
        const page = await browser.newPage({ viewport: { width: 900, height: 650 } });
        await page.setContent(`<!doctype html><html><body>
          <form id="form"><label for="category">Kategori</label><select id="category" name="kategori" required>
            <option value="">Pilih kategori</option><option value="bank">Voucher Bank</option><option value="kas">Voucher Kas</option>
          </select><button type="reset">Reset</button></form>
          <dialog id="dialog"><form><label for="month">Bulan</label><select id="month" name="bulan" required><option value="8">Agustus</option><option value="9">September</option></select></form></dialog>
          <div class="bukti-category-select"><select id="existing"><option>Asli</option></select></div>
          <select id="native" data-native-select><option>Native</option></select>
          <select id="multiple" multiple><option>Multi</option></select>
        </body></html>`);
        await page.addStyleTag({ content: ':root { --system-font: "Manrope", "Segoe UI", Arial, sans-serif; } body { font-size: 22px; } dialog { font-size: 24px; }' });
        await page.addStyleTag({ path: path.resolve(__dirname, '../public/assets/global-select.css') });
        await page.addScriptTag({ path: path.resolve(__dirname, '../public/assets/global-select.js') });
        const trigger = page.locator('#category + .global-select-trigger');
        assert.strictEqual(await trigger.count(), 1);
        assert.strictEqual(await page.locator('#existing + .global-select-trigger').count(), 0);
        assert.strictEqual(await page.locator('#native + .global-select-trigger').count(), 0);
        assert.strictEqual(await page.locator('#multiple + .global-select-trigger').count(), 0);
        await trigger.click();
        const categoryTypography = await page.locator('.global-select-menu:visible .global-select-option').first().evaluate((option) => {
            const style = getComputedStyle(option);
            return { fontSize: style.fontSize, fontFamily: style.fontFamily, lineHeight: style.lineHeight };
        });
        assert.strictEqual(categoryTypography.fontSize, '10px');
        assert(categoryTypography.fontFamily.includes('Manrope'));
        assert.strictEqual(categoryTypography.lineHeight, '14px');
        assert.strictEqual(await trigger.evaluate((button) => getComputedStyle(button).fontSize), '10px');
        await page.getByRole('option', { name: 'Voucher Bank' }).click();
        assert.strictEqual(await page.locator('#category').inputValue(), 'bank');
        assert.strictEqual(await trigger.innerText(), 'Voucher Bank');
        assert.strictEqual(await page.locator('#form').evaluate((form) => new FormData(form).get('kategori')), 'bank');
        await page.locator('#form button[type=reset]').click();
        await page.waitForTimeout(20);
        assert.strictEqual(await trigger.innerText(), 'Pilih kategori');
        assert.strictEqual(await page.locator('#form').evaluate((form) => form.reportValidity()), false);
        assert.strictEqual(await trigger.evaluate((button) => button === document.activeElement), true);
        await page.locator('#category').evaluate((select) => { select.value = 'kas'; });
        await page.waitForFunction(() => document.querySelector('#category + button span').textContent === 'Voucher Kas');
        await page.evaluate(() => document.querySelector('#dialog').showModal());
        const month = page.locator('#month + .global-select-trigger');
        await month.click();
        assert.strictEqual(await page.locator('#dialog .global-select-menu:visible').count(), 1);
        const modalTypography = await page.locator('#dialog .global-select-menu:visible .global-select-option').first().evaluate((option) => ({ fontSize: getComputedStyle(option).fontSize, fontFamily: getComputedStyle(option).fontFamily }));
        assert.strictEqual(modalTypography.fontSize, '10px');
        assert(modalTypography.fontFamily.includes('Manrope'));
        await page.getByRole('option', { name: 'September' }).click();
        assert.strictEqual(await page.locator('#month').inputValue(), '9');
        await month.press('ArrowDown');
        await page.keyboard.press('Escape');
        assert.strictEqual(await month.getAttribute('aria-expanded'), 'false');
        assert.strictEqual(await page.locator('#dialog').evaluate((dialog) => dialog.open), true);
        await month.click();
        await page.locator('#dialog').evaluate((dialog) => dialog.close());
        await page.waitForFunction(() => document.querySelector('#month + button').getAttribute('aria-expanded') === 'false');
        assert.strictEqual(await month.getAttribute('aria-expanded'), 'false');
        await page.evaluate(() => { const select = document.createElement('select'); select.id = 'dynamic'; select.innerHTML = '<option value="a">A</option><option value="b">B</option>'; document.body.append(select); });
        await page.waitForSelector('#dynamic + .global-select-trigger');
        await page.locator('#dynamic').evaluate((select) => { select.disabled = true; });
        await page.waitForFunction(() => document.querySelector('#dynamic + button').disabled);
        console.log('Global select interactions: passed');
    } finally { await browser.close(); }
})().catch((error) => { console.error(error); process.exitCode = 1; });
