import { getSearchParamsAndAppendOrDelete, getSearchParamsAndDelete, updateUrl } from './helpers';

export const activeFilterPills = () => {
  let abortController: AbortController;

  const initialize = (fetchAndUpdateResultsFunction: (updatedParams: URLSearchParams) => void) => {
    cleanup();

    abortController = new AbortController();
    const activeFilterPillElements = document.querySelectorAll('.js-active-filter-pill');

    activeFilterPillElements.forEach((activeFilterPillElement) => {
      activeFilterPillElement.addEventListener('click', (event) => {
        event.preventDefault();

        const { currentTarget: element } = event;
        if (!(element instanceof HTMLElement)) {
          return;
        }

        const { key = '', value = '' } = element.dataset;

        const params = value === '' ? getSearchParamsAndDelete(key) : getSearchParamsAndAppendOrDelete(false, key, value);

        removeActiveFilterPillElement(element);

        updateUrl(params, fetchAndUpdateResultsFunction);
      }, { signal: abortController.signal });
    });
  };

  const removeActiveFilterPillElement = (activeFilterPillElement: HTMLElement) => {
    const listItemElement = activeFilterPillElement.closest('li');
    const listElement = activeFilterPillElement.closest('ul');

    activeFilterPillElement.remove();
    if (listItemElement) {
      listItemElement.remove();
    }

    if (listElement && listElement.children.length === 0) {
      listElement.remove();
    }

    cleanup();
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
