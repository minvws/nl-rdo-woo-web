import { getSearchParams, getSearchParamsAndDelete, getSearchParamsAndSet, updateUrl } from './helpers';

export const dateFilters = () => {
  let abortController: AbortController;

  const initialize = (fetchAndUpdateResultsFunction: (updatedParams: URLSearchParams) => void) => {
    cleanup();

    abortController = new AbortController();
    const dateFilterElements = document.querySelectorAll('.js-date-filter') as NodeListOf<HTMLInputElement>;

    dateFilterElements.forEach((dateFilterElement) => {
      dateFilterElement.addEventListener('blur', (event) => {
        const { target } = event;
        if (!(target instanceof HTMLInputElement)) {
          return;
        }

        const { name, value: date } = target;

        if (date === (getSearchParams().get(name) || '')) {
          // Date did not change
          return;
        }

        const isDateInvalid = new Date(date).toString() === 'Invalid Date';
        const params = isDateInvalid ? getSearchParamsAndDelete(name) : getSearchParamsAndSet(name, date);

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
