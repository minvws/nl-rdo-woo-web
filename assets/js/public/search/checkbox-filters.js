import { getSearchParamsAndAppendOrDelete, updateUrl } from './helpers';

export const checkboxFilters = () => {
    let abortController = null;

    const initialize = (fetchAndUpdateResultsFunction) => {
        if (abortController) {
            abortController.abort();
        }

        abortController = new AbortController();
        const checkboxElements = document.querySelectorAll('.js-search-filter-checkbox');

        checkboxElements.forEach((checkboxElement) => {
            checkboxElement.addEventListener('change', (event) => {
                const { checked, name, value } = event.target;
                const params = getSearchParamsAndAppendOrDelete(checked, name, value);

                updateUrl(params, fetchAndUpdateResultsFunction);
            }, { signal: abortController.signal });
        });
    }

    return {
        initialize,
    };
}
