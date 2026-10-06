<?php

/** @var string $cardId */
$cardSelector = '#' . $cardId;
?>
<script>
(() => {
    const cardSelector = <?= json_encode($cardSelector, JSON_THROW_ON_ERROR) ?>;
    let pendingRequest = null;

    const replaceTableCard = async (url, updateHistory = true) => {
        const currentCard = document.querySelector(cardSelector);
        if (!currentCard) {
            window.location.assign(url);
            return;
        }

        pendingRequest?.abort();
        const request = new AbortController();
        pendingRequest = request;
        currentCard.classList.add('is-loading');

        try {
            const response = await fetch(url, {
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                signal: request.signal,
            });
            if (!response.ok) throw new Error('Table request failed.');

            const responseDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
            const nextCard = responseDocument.querySelector(cardSelector);
            if (!nextCard) throw new Error('Table card is unavailable.');

            currentCard.replaceWith(nextCard);
            if (updateHistory) history.pushState({tableCard: cardSelector}, '', url);
        } catch (error) {
            if (error.name !== 'AbortError') window.location.assign(url);
        } finally {
            if (pendingRequest === request) pendingRequest = null;
            currentCard.classList.remove('is-loading');
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest(`${cardSelector} .pagination a`);
        if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        replaceTableCard(link.href);
    });

    document.addEventListener('change', (event) => {
        const select = event.target.closest(`${cardSelector} [data-async-table-length]`);
        if (!select) return;
        const form = select.form;
        if (!form) return;
        const url = new URL(form.action, window.location.href);
        const query = new URLSearchParams(new FormData(form));
        query.delete('page');
        url.search = query.toString();
        replaceTableCard(url.toString());
    });

    window.addEventListener('popstate', () => replaceTableCard(window.location.href, false));
})();
</script>
