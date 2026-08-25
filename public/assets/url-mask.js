(() => {
    const productionHosts = new Set(['kanwil-surabaya.com', 'www.kanwil-surabaya.com']);

    if (!productionHosts.has(window.location.hostname)) {
        return;
    }

    // Keep the real route available for CRUD refreshes before masking the bar.
    window.__operationalRouteUrl = window.location.href;

    const showDomainOnly = () => {
        const domainUrl = `${window.location.origin}/`;

        if (window.location.href !== domainUrl) {
            window.history.replaceState(window.history.state, document.title, domainUrl);
        }
    };

    showDomainOnly();
    window.addEventListener('hashchange', showDomainOnly);
})();
