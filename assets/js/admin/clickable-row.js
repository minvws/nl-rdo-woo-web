export const clickableRows = () => {
    let abortController = null;

    const initialize = () => {
        removeEventListeners();

        const clickableRowAnchors = document.querySelectorAll('.js-woo-clickable-row__a');
        if (!clickableRowAnchors.length) {
            return;
        }

        abortController = new AbortController();
        clickableRowAnchors.forEach((clickableRowAnchor) => {
            const tableRow = clickableRowAnchor.closest('tr');
            if (!tableRow) {
                return;
            }

            if (tableRow.classList.contains('woo-clickable-row')) {
                return;
            }

            tableRow.classList.add('woo-clickable-row');
            tableRow.addEventListener('click', () => {
                window.location.href = clickableRowAnchor.href;
            }, { signal: abortController.signal });
        });
    };

    const removeEventListeners = () => {
        if (abortController) {
            abortController.abort();
        }
    };

    return {
        initialize,
        removeEventListeners,
    }
}
