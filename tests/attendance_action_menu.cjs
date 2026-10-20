const assert = require('assert');
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'app/Views/sdm/sdm_jatim.php'), 'utf8');
const reference = fs.readFileSync(path.join(root, 'app/Views/sdm/sdm_jatim_list.php'), 'utf8');
const buttonFor = (text, attribute) => {
    const match = text.match(new RegExp(`<button\\b[^>]*\\b${attribute}\\b[^>]*>[\\s\\S]*?<\\/button>`));
    assert(match, `missing ${attribute}`);
    return match[0];
};
const attendance = ['data-open-attendance-upload', 'data-open-attendance-export', 'data-open-attendance-delete'].map((name) => buttonFor(source, name));
const sample = [buttonFor(reference, 'data-sdm-profile-create'), buttonFor(reference, 'data-sdm-profile-delete-all')];

(async () => {
    const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
    try {
        const page = await browser.newPage();
        await page.setContent(`<body><div class="filter-actions"><details class="lr-action-menu lr-filter-menu" open><summary>Menu</summary><div class="lr-action-menu-popover">${attendance.join('')}</div></details><details class="lr-action-menu lr-filter-menu" open><summary>Contoh</summary><div class="lr-action-menu-popover">${sample.join('')}</div></details></div></body>`);
        await page.addStyleTag({ path: path.join(root, 'public/assets/app.css') });
        await page.addStyleTag({ content: '.filter-actions { justify-content: flex-start !important; gap: 300px !important; margin-left: 250px !important; }' });
        const style = async (selector) => page.locator(selector).evaluate((node) => {
            const css = getComputedStyle(node);
            return ['fontFamily', 'fontSize', 'fontWeight', 'color', 'backgroundColor', 'borderWidth', 'padding', 'minHeight', 'textAlign'].map((name) => css[name]);
        });
        const normal = '[data-sdm-profile-create]';
        const danger = '[data-sdm-profile-delete-all]';
        for (const selector of ['[data-open-attendance-upload]', '[data-open-attendance-export]']) {
            assert.deepStrictEqual(await style(selector), await style(normal), `${selector} differs from standard`);
        }
        assert.deepStrictEqual(await style('[data-open-attendance-delete]'), await style(danger), 'delete appearance differs');
        await page.locator(normal).hover();
        const normalHover = await style(normal);
        await page.locator('[data-open-attendance-upload]').hover();
        assert.deepStrictEqual(await style('[data-open-attendance-upload]'), normalHover, 'normal hover differs');
        await page.locator(danger).hover();
        const dangerHover = await style(danger);
        await page.locator('[data-open-attendance-delete]').hover();
        assert.deepStrictEqual(await style('[data-open-attendance-delete]'), dangerHover, 'delete hover differs');
        console.log('Attendance action menu styles and hover: passed');
    } finally { await browser.close(); }
})().catch((error) => { console.error(error); process.exitCode = 1; });
