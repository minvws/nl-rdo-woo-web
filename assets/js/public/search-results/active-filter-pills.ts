import {
  getCheckboxFilterElements,
  getSearchParamsAndDelete,
  getUpdatedParamsFromCheckboxFilter,
  updateUrl,
} from './helpers';

export const activeFilterPills = () => {
  let abortController: AbortController;

  const initialize = (
    fetchAndUpdateResultsFunction: (updatedParams: URLSearchParams) => void,
  ) => {
    cleanup();

    abortController = new AbortController();
    const activeFilterPillElements = getActiveFilterPillElements();

    activeFilterPillElements.forEach((activeFilterPillElement) => {
      activeFilterPillElement.addEventListener(
        'click',
        (event) => {
          event.preventDefault();

          const { currentTarget: element } = event;
          if (!(element instanceof HTMLAnchorElement)) {
            return;
          }

          const params = getUpdatedParams(element);
          removeActiveFilterPillElement(element);
          updateUrl(params, fetchAndUpdateResultsFunction);
        },
        { signal: abortController.signal },
      );
    });
  };

  const getUpdatedParams = (activeFilterPillElement: HTMLAnchorElement) => {
    const relatedCheckboxElement = getRelatedCheckboxElement(
      activeFilterPillElement,
    );
    if (relatedCheckboxElement) {
      return getUpdatedParamsFromCheckboxFilter(relatedCheckboxElement, false);
    }

    const { key } = getKeyAndValue(activeFilterPillElement);
    return getSearchParamsAndDelete(key);
  };

  const getActiveFilterPillElements = () =>
    [
      ...document.querySelectorAll('.js-active-filter-pill'),
    ] as HTMLAnchorElement[];

  const getCheckboxFilterByNameAndValue = (name: string, value: string) =>
    getCheckboxFilterElements().find(
      (checkboxElement) =>
        checkboxElement.name === name && checkboxElement.value === value,
    );

  const getRelatedCheckboxElement = (
    activeFilterPillElement: HTMLAnchorElement,
  ) => {
    const { key, value } = getKeyAndValue(activeFilterPillElement);
    return getCheckboxFilterByNameAndValue(key, value);
  };

  const getKeyAndValue = (activeFilterPillElement: HTMLAnchorElement) => {
    const { key = '', value = '' } = activeFilterPillElement.dataset;
    return { key, value };
  };

  const removeActiveFilterPillElement = (
    activeFilterPillElement: HTMLElement,
  ) => {
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
