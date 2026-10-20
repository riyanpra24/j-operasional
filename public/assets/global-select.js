(() => {
    'use strict';

    const controls = new WeakMap();
    let openControl = null;
    let nextId = 0;

    const close = (restoreFocus = false) => {
        if (!openControl) return;
        const control = openControl;
        openControl = null;
        window.hideJaksaSelectOverlay(control.menu);
        control.menu.hidden = true;
        control.trigger.setAttribute('aria-expanded', 'false');
        if (restoreFocus) control.trigger.focus();
    };

    // Fixed positioning can be relative to a transformed dialog, not the viewport.
    // Measure its actual origin and scale before translating viewport coordinates.
    const positionMenu = (trigger, menu, boundary = null, ensureSpace = false) => {
        const anchor = trigger.getBoundingClientRect();
        const bounds = {
            left: 4,
            top: 4,
            right: document.documentElement.clientWidth - 4,
            bottom: document.documentElement.clientHeight - 4,
        };
        if (bounds.right <= bounds.left || bounds.bottom <= bounds.top) return;
        menu.style.left = '0px';
        menu.style.top = '0px';
        menu.style.width = '100px';
        menu.style.maxHeight = '';
        const origin = menu.getBoundingClientRect();
        const scaleX = origin.width / 100 || 1;
        const scaleY = origin.height / menu.offsetHeight || 1;
        const width = Math.min(anchor.width, bounds.right - bounds.left);
        const left = Math.min(Math.max(anchor.left, bounds.left), bounds.right - width);
        menu.style.width = `${width / scaleX}px`;
        const below = Math.max(0, bounds.bottom - anchor.bottom - 5);
        const above = Math.max(0, anchor.top - bounds.top - 5);
        const naturalHeight = menu.getBoundingClientRect().height;
        const opensAbove = naturalHeight > below && above > 0;
        const available = opensAbove ? above : below;
        menu.style.maxHeight = `${Math.max(0, Math.min(260, available) / scaleY)}px`;
        menu.style.overflowY = 'auto';
        const height = menu.getBoundingClientRect().height;
        const top = opensAbove ? anchor.top - height - 5 : anchor.bottom + 5;
        menu.style.left = `${(left - origin.left) / scaleX}px`;
        menu.style.top = `${(top - origin.top) / scaleY}px`;
    };
    window.positionJaksaSelectMenu = positionMenu;
    window.showJaksaSelectOverlay = (menu) => {
        if (typeof menu.showPopover !== 'function') return false;
        menu.setAttribute('popover', 'manual');
        if (!menu.matches(':popover-open')) menu.showPopover();
        return true;
    };
    window.hideJaksaSelectOverlay = (menu) => {
        if (typeof menu.hidePopover === 'function' && menu.matches(':popover-open')) menu.hidePopover();
    };
    const position = (control, ensureSpace = false) => positionMenu(control.trigger, control.menu, control.trigger.closest('dialog[open], [role="dialog"][aria-modal="true"], [role="alertdialog"][aria-modal="true"]'), ensureSpace);

    const sync = (control) => {
        const { select, trigger, text, menu } = control;
        const selected = select.selectedOptions[0];
        text.textContent = selected ? selected.textContent.trim() : '';
        trigger.disabled = select.matches(':disabled');
        trigger.setAttribute('aria-required', select.required ? 'true' : 'false');
        const label = select.getAttribute('aria-label') || Array.from(select.labels || []).map((item) => item.textContent.trim()).join(' ');
        if (label) trigger.setAttribute('aria-label', label);
        menu.querySelectorAll('[data-select-index]').forEach((item) => {
            const option = select.options[Number(item.dataset.selectIndex)];
            if (option) item.setAttribute('aria-selected', option.selected ? 'true' : 'false');
        });
    };

    const buildOptions = (control) => {
        const { select, menu } = control;
        menu.replaceChildren();
        const addOption = (option) => {
            if (option.hidden) return;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'global-select-option';
            button.setAttribute('role', 'option');
            button.dataset.selectIndex = String(option.index);
            button.textContent = option.textContent.trim();
            button.disabled = option.disabled || Boolean(option.closest('optgroup:disabled'));
            button.setAttribute('aria-selected', option.selected ? 'true' : 'false');
            menu.append(button);
        };
        Array.from(select.children).forEach((child) => {
            if (child.tagName === 'OPTION') addOption(child);
            if (child.tagName === 'OPTGROUP' && !child.hidden) {
                const group = document.createElement('div');
                group.className = 'global-select-group';
                group.textContent = child.label;
                menu.append(group);
                Array.from(child.children).forEach(addOption);
            }
        });
    };

    const setup = (select) => {
        if (controls.has(select) || select.multiple || select.size > 1 || select.closest('.bukti-category-select, .bukti-date-range-popover') || select.closest('[data-native-select]') || select.hidden) return;
        const wrapper = document.createElement('span');
        wrapper.className = 'global-select';
        select.before(wrapper);
        wrapper.append(select);
        select.classList.add('global-select-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'global-select-trigger';
        trigger.setAttribute('role', 'combobox');
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        const text = document.createElement('span');
        const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        icon.setAttribute('viewBox', '0 0 24 24');
        icon.setAttribute('aria-hidden', 'true');
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', 'm6 9 6 6 6-6');
        icon.append(path);
        trigger.append(text, icon);
        wrapper.append(trigger);

        const menu = document.createElement('div');
        menu.className = 'global-select-menu';
        menu.id = `global-select-options-${++nextId}`;
        menu.setAttribute('role', 'listbox');
        menu.hidden = true;
        trigger.setAttribute('aria-controls', menu.id);
        const control = { select, wrapper, trigger, text, menu };
        controls.set(select, control);

        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (openControl === control) { close(); return; }
            close();
            sync(control);
            buildOptions(control);
            const dialog = select.closest('dialog[open], [role="dialog"][aria-modal="true"], [role="alertdialog"][aria-modal="true"]');
            (dialog?.matches('dialog') ? dialog : dialog?.parentElement || document.body).append(menu);
            menu.hidden = false;
            window.showJaksaSelectOverlay(menu);
            openControl = control;
            trigger.setAttribute('aria-expanded', 'true');
            position(control, true);
        });
        trigger.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (openControl !== control) trigger.click();
                const options = Array.from(menu.querySelectorAll('.global-select-option:not(:disabled)'));
                (options.find((item) => item.getAttribute('aria-selected') === 'true') || options[0])?.focus();
            }
        });
        menu.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const item = event.target.closest('[data-select-index]');
            if (!item || item.disabled) return;
            select.selectedIndex = Number(item.dataset.selectIndex);
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
            sync(control);
            close(true);
        });
        menu.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); close(true); return; }
            if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;
            event.preventDefault();
            const options = Array.from(menu.querySelectorAll('.global-select-option:not(:disabled)'));
            const current = options.indexOf(document.activeElement);
            const index = Math.max(0, Math.min(options.length - 1, current + (event.key === 'ArrowDown' ? 1 : -1)));
            options[index]?.focus();
        });
        select.addEventListener('change', () => sync(control));
        select.addEventListener('focus', () => trigger.focus());
        select.addEventListener('invalid', () => { sync(control); trigger.focus(); });
        select.form?.addEventListener('reset', () => setTimeout(() => sync(control), 0));
        new MutationObserver(() => sync(control)).observe(select, { attributes: true, childList: true, subtree: true, characterData: true });
        sync(control);
    };

    const scan = (root) => {
        if (root.nodeType !== 1 && root.nodeType !== 9) return;
        if (root.matches?.('select')) setup(root);
        root.querySelectorAll('select').forEach(setup);
    };
    scan(document);
    new MutationObserver((mutations) => mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
        if (node.nodeType === 1 && !node.closest('.global-select, .global-select-menu')) scan(node);
    }))).observe(document.body, { childList: true, subtree: true });
    const syncAll = () => {
        if (openControl && (!openControl.trigger.isConnected || openControl.trigger.getClientRects().length === 0)) close();
        document.querySelectorAll('select.global-select-native').forEach((select) => {
        const control = controls.get(select);
        if (control) sync(control);
        });
    };
    document.addEventListener('click', (event) => {
        if (openControl && !openControl.wrapper.contains(event.target) && !openControl.menu.contains(event.target)) close();
        // Halaman dapat mengisi select melalui kode saat membuka popup.
        setTimeout(syncAll, 0);
    });
    // Beberapa formulir mengisi nilai secara asinkron tanpa mengirim event change.
    window.setInterval(() => { if (!document.hidden) syncAll(); }, 300);
    document.addEventListener('close', (event) => {
        if (openControl && event.target.contains(openControl.wrapper)) close();
    }, true);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && openControl) { event.preventDefault(); close(true); }
    });
    document.addEventListener('scroll', () => { if (openControl) position(openControl); }, true);
    window.addEventListener('resize', () => { if (openControl) position(openControl); });
})();
