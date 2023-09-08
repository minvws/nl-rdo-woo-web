import { getSearchParamsAndAppendOrDelete, getSearchParamsAndDelete, updateUrl } from './helpers';

export const activeFilterPills = () => {
    let abortController = null;

    const initialize = (fetchAndUpdateResultsFunction) => {
        if (abortController) {
            abortController.abort();
        }

        abortController = new AbortController();
        const activeFilterPillElements = document.querySelectorAll('.js-active-filter-pill');

        activeFilterPillElements.forEach((activeFilterPillElement) => {
            activeFilterPillElement.addEventListener('click', (event) => {
                event.preventDefault();

                const { target: element } = event;
                const { key, value } = element.dataset;

                const params = value === '' ? getSearchParamsAndDelete(key) : getSearchParamsAndAppendOrDelete(false, key, value);

                removeActiveFilterPillElement(element);

                updateUrl(params, fetchAndUpdateResultsFunction);
            }, { signal: abortController.signal });
        });
    }

    const removeActiveFilterPillElement = (activeFilterPillElement) => {
        const listItemElement = activeFilterPillElement.closest('li');
        const listElement = activeFilterPillElement.closest('ul');

        activeFilterPillElement.remove();
        if (listItemElement) {
            listItemElement.remove();
        }

        if (listElement && listElement.children.length === 0) {
            listElement.remove();
        }
    }

    return {
        initialize,
    };
}
