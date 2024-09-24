import { getCheckboxFilterElements, updateUrl, getUpdatedParamsFromCheckboxFilter } from './helpers';

export const checkboxFilters = () => {
  let abortController: AbortController;

  const initialize = (fetchAndUpdateResultsFunction: (updatedParams: URLSearchParams) => void) => {
    cleanup();

    abortController = new AbortController();
    getCheckboxFilterElements().forEach((checkboxElement) => {
      checkboxElement.addEventListener('change', (event) => {
        const { target } = event;
        if (!(target instanceof HTMLInputElement)) {
          return;
        }

        updateUrl(getUpdatedParamsFromCheckboxFilter(target, target.checked), fetchAndUpdateResultsFunction);
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
