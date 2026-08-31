(() => {
    'use strict';

    const controlSelector = 'input:not([type="hidden"]), select, textarea';

    const findExistingMarker = (label) => Array.from(label.querySelectorAll('span')).find((span) => (
        span.classList.contains('required-marker')
        || (span.classList.contains('required') && span.textContent.trim() === '*')
        || span.textContent.trim() === '*'
    ));

    const wrapTrailingMarker = (label) => {
        for (const node of Array.from(label.childNodes).reverse()) {
            if (node.nodeType !== Node.TEXT_NODE || !/\*\s*$/.test(node.textContent || '')) {
                continue;
            }

            node.textContent = (node.textContent || '').replace(/\*\s*$/, '').trimEnd();
            const marker = document.createElement('span');
            marker.className = 'required-marker';
            marker.setAttribute('aria-hidden', 'true');
            marker.textContent = '*';
            label.append(' ', marker);
            return marker;
        }

        return null;
    };

    const controlsForLabel = (label) => {
        if (label.htmlFor) {
            const control = document.getElementById(label.htmlFor);
            return control?.matches(controlSelector) ? [control] : [];
        }

        const nestedControls = Array.from(label.querySelectorAll(controlSelector));
        if (nestedControls.length > 0) {
            return nestedControls;
        }

        const group = label.closest('.form-group');
        return group ? Array.from(group.querySelectorAll(controlSelector)) : [];
    };

    const synchronizeLabel = (label) => {
        if (!(label instanceof HTMLLabelElement)) {
            return;
        }

        const controls = controlsForLabel(label);
        let marker = findExistingMarker(label) || wrapTrailingMarker(label);

        // Existing manual markers without a form control still receive the
        // same global appearance, but their visibility remains page-controlled.
        if (controls.length === 0) {
            marker?.classList.add('required-marker');
            return;
        }

        const isRequired = controls.some((control) => control.required && !control.disabled);
        if (isRequired && !marker) {
            marker = document.createElement('span');
            marker.textContent = '*';
            label.append(' ', marker);
        }

        if (marker) {
            marker.classList.add('required-marker');
            marker.setAttribute('aria-hidden', 'true');
            marker.hidden = !isRequired;
        }
    };

    const labelsForControl = (control) => {
        const labels = Array.from(control.labels || []);
        if (labels.length > 0) {
            return labels;
        }

        const fallback = control.closest('.form-group')?.querySelector('label');
        return fallback ? [fallback] : [];
    };

    const scan = (root = document) => {
        const labels = new Set();
        if (root instanceof HTMLLabelElement) {
            labels.add(root);
        }
        if (root instanceof Element && root.matches(controlSelector)) {
            labelsForControl(root).forEach((label) => labels.add(label));
        }

        root.querySelectorAll?.('label').forEach((label) => labels.add(label));
        root.querySelectorAll?.(controlSelector).forEach((control) => {
            labelsForControl(control).forEach((label) => labels.add(label));
        });
        labels.forEach(synchronizeLabel);
    };

    const start = () => {
        scan();

        new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes') {
                    labelsForControl(mutation.target).forEach(synchronizeLabel);
                    return;
                }

                mutation.addedNodes.forEach((node) => {
                    if (node instanceof Element) {
                        scan(node);
                    }
                });
            });
        }).observe(document.body, {
            subtree: true,
            childList: true,
            attributes: true,
            attributeFilter: ['required', 'disabled'],
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
