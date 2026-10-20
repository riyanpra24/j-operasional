const { chromium } = require('playwright');
const path = require('path');
const assert = require('assert');

const assets = (name) => path.resolve(__dirname, '../public/assets', name);
const options = Array.from({ length: 15 }, (_, i) => `<option value="${i}">Pilihan ${i + 1} dengan nama yang cukup panjang</option>`).join('');
const box = async (locator) => locator.evaluate((element) => {
    const rect = element.getBoundingClientRect();
    return { left: rect.left, right: rect.right, top: rect.top, bottom: rect.bottom, width: rect.width, height: rect.height };
});
const assertPosition = async (page, trigger, boundary) => {
    await trigger.click();
    const menu = page.locator('.global-select-menu:visible');
    await menu.waitFor({ state: 'visible' });
    const [anchor, panel, card] = await Promise.all([box(trigger), box(menu), boundary ? box(boundary) : null]);
    const viewport = page.viewportSize();
    assert(panel.left >= 3 && panel.right <= viewport.width - 3, `menu outside viewport horizontally: ${JSON.stringify(panel)}`);
    assert(panel.top >= 3 && panel.bottom <= viewport.height - 3, `menu outside viewport vertically: ${JSON.stringify(panel)}`);
    assert(Math.abs(panel.left - anchor.left) < 3, `menu not aligned: ${JSON.stringify({ anchor, panel })}`);
    assert(Math.abs(panel.width - anchor.width) < 3, `menu wrong width: ${JSON.stringify({ anchor, panel })}`);
    assert(Math.min(Math.abs(panel.top - anchor.bottom - 5), Math.abs(anchor.top - panel.bottom - 5)) < 4, `menu not next to control: ${JSON.stringify({ anchor, panel })}`);
    const option = menu.locator('.global-select-option:not(:disabled)').first();
    assert(await option.evaluate((node) => {
        const r = node.getBoundingClientRect();
        return node.contains(document.elementFromPoint(r.left + Math.min(15, r.width / 2), r.top + Math.min(12, r.height / 2)));
    }), 'menu is covered or clipped');
    await option.click();
    assert.strictEqual(await trigger.getAttribute('aria-expanded'), 'false');
};

(async () => {
    const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
    try {
        for (const viewport of [{ width: 1366, height: 768 }, { width: 900, height: 650 }, { width: 390, height: 700 }]) {
            const page = await browser.newPage({ viewport });
            await page.setContent(`<!doctype html><html><body>
              <div class="content-shell"><main class="main-content"><section class="filter-panel"><form>
                <div class="form-group"><label for="filterText">Cari dokumen</label><input id="filterText" placeholder="Cari dokumen"></div>
                <div class="form-group"><label for="filterType">Jenis</label><select id="filterType"><option>Semua jenis</option>${options}</select></div>
                <div class="form-group"><label for="filterDate">Dari tanggal diterima</label><input id="filterDate" type="date"></div>
              </form></section></main></div>
              <div style="padding:20px"><label for="pageSelect">Filter</label><select id="pageSelect">${options}</select></div>
              <div style="position:fixed;right:20px;bottom:14px;width:240px"><label for="bottomSelect">Ekspedisi</label><select id="bottomSelect">${options}</select></div>
              <div class="input-modal open" id="customModal" hidden><button class="modal-backdrop" type="button"></button>
                <section class="modal-dialog modal-dialog-compact" role="dialog" aria-modal="true" style="transform: translateY(0) scale(.96); transition:none">
                  <header class="modal-header">Input Dokumen Masuk</header>
                  <form style="display:flex;flex:1;min-height:0;flex-direction:column"><div class="modal-body" id="scrollBody">
                    <div style="height:10px"></div><label>Perihal<select id="upper">${options}</select></label><input id="dateInput" type="date">
                    <div style="height:120px"></div><label>Jenis<select id="lower">${options}</select></label>
                    <div style="height:120px"></div><label>Ekspedisi<select id="scrolled">${options}</select></label>
                    <div style="height:120px"></div><button type="button" id="categoryTrigger" class="bukti-category-trigger">Pilih kategori</button><div style="height:100px"></div>
                  </div><footer class="modal-footer">Simpan dokumen</footer></form>
                </section>
              </div>
              <dialog id="nativeDialog" style="width:min(550px,90vw);height:min(480px,80vh);overflow:hidden;transform:scale(.94)">
                <label>Bulan<select id="nativeMonth">${options}</select></label>
              </dialog>
            </body></html>`);
            await page.addStyleTag({ path: assets('app.css') });
            await page.addStyleTag({ path: assets('flowui-theme.css') });
            await page.addStyleTag({ path: assets('jaksa-background.css') });
            await page.addStyleTag({ path: assets('global-select.css') });
            await page.addScriptTag({ path: assets('global-select.js') });
            const filterHeights = await page.evaluate(() => ({
                input: document.querySelector('#filterText').getBoundingClientRect().height,
                select: document.querySelector('#filterType + button').getBoundingClientRect().height,
                date: document.querySelector('#filterDate').getBoundingClientRect().height,
                inputFont: getComputedStyle(document.querySelector('#filterText')).fontSize,
                selectFont: getComputedStyle(document.querySelector('#filterType + button')).fontSize,
            }));
            assert(Math.abs(filterHeights.select - filterHeights.input) < 1, `filter dropdown height must match text input: ${JSON.stringify(filterHeights)}`);
            assert(Math.abs(filterHeights.select - filterHeights.date) < 1, `filter dropdown height must match date input: ${JSON.stringify(filterHeights)}`);
            assert.strictEqual(filterHeights.selectFont, filterHeights.inputFont, `filter dropdown font must match inputs: ${JSON.stringify(filterHeights)}`);
            await assertPosition(page, page.locator('#pageSelect + button'), null);
            const bottomTrigger = page.locator('#bottomSelect + button');
            await bottomTrigger.click();
            const [bottomAnchor, bottomMenu] = await Promise.all([box(bottomTrigger), box(page.locator('.global-select-menu:visible'))]);
            assert(Math.abs(bottomAnchor.top - bottomMenu.bottom - 5) < 4, `bottom dropdown should open above: ${JSON.stringify({ bottomAnchor, bottomMenu })}`);
            await page.locator('.global-select-menu:visible .global-select-option').first().click();
            await page.locator('#customModal').evaluate((node) => { node.hidden = false; });
            const card = page.locator('#customModal .modal-dialog');
            const fontSizes = await page.evaluate(() => ({
                input: getComputedStyle(document.querySelector('#dateInput')).fontSize,
                trigger: getComputedStyle(document.querySelector('#upper + button')).fontSize,
                option: getComputedStyle(document.querySelector('.global-select-option')).fontSize,
            }));
            assert.strictEqual(fontSizes.trigger, fontSizes.input, 'dropdown text should match date input');
            assert.strictEqual(fontSizes.option, fontSizes.input, 'dropdown options should match date input');
            await assertPosition(page, page.locator('#upper + button'), card);
            await assertPosition(page, page.locator('#lower + button'), card);
            await page.locator('#scrolled + button').scrollIntoViewIfNeeded();
            await assertPosition(page, page.locator('#scrolled + button'), card);
            await card.evaluate((node) => { node.style.height = '260px'; });
            await page.locator('#categoryTrigger').scrollIntoViewIfNeeded();
            await page.evaluate(() => {
                const menu = document.createElement('div');
                menu.className = 'bukti-category-options';
                menu.id = 'categoryMenu';
                menu.style.cssText = 'position:fixed;z-index:150;right:auto;animation:none';
                for (let i = 0; i < 10; i++) {
                    const item = document.createElement('button');
                    item.type = 'button'; item.textContent = `Kategori ${i + 1}`;
                    menu.append(item);
                }
                document.querySelector('#customModal').append(menu);
                window.showJaksaSelectOverlay(menu);
                window.positionJaksaSelectMenu(document.querySelector('#categoryTrigger'), menu, document.querySelector('#customModal .modal-dialog'), true);
            });
            const [categoryAnchor, categoryMenu, categoryCard] = await Promise.all([box(page.locator('#categoryTrigger')), box(page.locator('#categoryMenu')), box(card)]);
            assert(Math.min(Math.abs(categoryMenu.top - categoryAnchor.bottom - 5), Math.abs(categoryAnchor.top - categoryMenu.bottom - 5)) < 4, 'category menu should open next to its field');
            assert(categoryMenu.bottom <= viewport.height - 3, 'category menu should fit inside the viewport');
            if (viewport.width > 700) assert(categoryMenu.bottom > categoryCard.bottom + 10 || categoryMenu.top < categoryCard.top - 10, `category menu should extend beyond its card: ${JSON.stringify({ categoryMenu, categoryCard, categoryAnchor })}`);
            await page.locator('#categoryMenu button').last().scrollIntoViewIfNeeded();
            await page.locator('#categoryMenu button').last().click();
            await page.locator('#categoryMenu').evaluate((node) => node.remove());
            await card.evaluate((node) => { node.style.height = ''; });
            const scrollTrigger = page.locator('#lower + button');
            await scrollTrigger.scrollIntoViewIfNeeded();
            await scrollTrigger.click();
            await page.locator('#scrollBody').evaluate((node) => { node.scrollTop += 30; });
            await page.waitForTimeout(100);
            const anchorAfterScroll = await box(scrollTrigger);
            const menuAfterScroll = await box(page.locator('.global-select-menu:visible'));
            assert(Math.abs(anchorAfterScroll.left - menuAfterScroll.left) < 3, 'scroll horizontal alignment');
            assert(Math.min(Math.abs(menuAfterScroll.top - anchorAfterScroll.bottom - 5), Math.abs(anchorAfterScroll.top - menuAfterScroll.bottom - 5)) < 4, 'scroll vertical alignment');
            await page.locator('#customModal').evaluate((node) => { node.hidden = true; });
            await page.waitForFunction(() => document.querySelector('#lower + button').getAttribute('aria-expanded') === 'false');
            await page.locator('#nativeDialog').evaluate((node) => { node.style.height = '135px'; });
            await page.locator('#nativeDialog').evaluate((node) => node.showModal());
            await page.locator('#nativeMonth + button').click();
            const [nativeCard, nativeMenu] = await Promise.all([box(page.locator('#nativeDialog')), box(page.locator('.global-select-menu:visible'))]);
            assert(nativeMenu.bottom > nativeCard.bottom + 10, 'native dialog menu should extend beyond its card');
            await page.locator('.global-select-menu:visible .global-select-option').last().scrollIntoViewIfNeeded();
            await page.locator('.global-select-menu:visible .global-select-option').last().click();
            await assertPosition(page, page.locator('#nativeMonth + button'), page.locator('#nativeDialog'));
            await page.locator('#nativeDialog').evaluate((node) => node.close());
            await page.close();
            console.log(`Dropdown layout ${viewport.width}x${viewport.height}: passed`);
        }
    } finally { await browser.close(); }
})().catch((error) => { console.error(error); process.exitCode = 1; });
