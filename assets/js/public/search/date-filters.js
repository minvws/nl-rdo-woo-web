import { getSearchParams, getSearchParamsAndDelete, getSearchParamsAndSet, updateUrl } from './helpers';

export const dateFilters = () => {
    let abortController = null;

    const initialize = (fetchAndUpdateResultsFunction) => {
        if (abortController) {
            abortController.abort();
        }

        abortController = new AbortController();
        const dateFilterElements = document.querySelectorAll('.js-date-filter');

        dateFilterElements.forEach((dateFilterElement) => {
            dateFilterElement.addEventListener('blur', (event) => {
                const { name, value: date } = event.target;

                if (date === (getSearchParams().get(name) || '')) {
                    // Date did not change
                    return;
                }

                const isDateInvalid = new Date(date).toString() === 'Invalid Date';
                const params = isDateInvalid ? getSearchParamsAndDelete(name) : getSearchParamsAndSet(name, date);

                updateUrl(params, fetchAndUpdateResultsFunction);
            }, { signal: abortController.signal });
        });
    }

    return {
        initialize,
    };
}
