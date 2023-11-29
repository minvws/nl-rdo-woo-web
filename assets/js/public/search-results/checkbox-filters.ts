import { getSearchParamsAndAppendOrDelete, updateUrl } from './helpers';

export const checkboxFilters = () => {
  let abortController: AbortController;

  const initialize = (fetchAndUpdateResultsFunction: (updatedParams: URLSearchParams) => void) => {
    cleanup();

    abortController = new AbortController();
    const checkboxElements = document.querySelectorAll('.js-search-filter-checkbox');

    checkboxElements.forEach((checkboxElement) => {
      checkboxElement.addEventListener('change', (event) => {
        const { target } = event;
        if (!(target instanceof HTMLInputElement)) {
          return;
        }

        const { checked, name, value } = target;
        const params = getSearchParamsAndAppendOrDelete(checked, name, value);

        updateUrl(params, fetchAndUpdateResultsFunction);
      }, { signal: abortController.signal });
    });
  };

  const cleanup = () => {
    if (abortController) {
      abortController.abort();
    }
  };

  return {
    cleanup,
    initialize,
  };
};
