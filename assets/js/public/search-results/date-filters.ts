import { hideElement, isDateInvalid, showElement } from '@js/utils';
import { isAfter } from 'date-fns';
import { getSearchParamsAndDelete, getSearchParamsAndSet, updateUrl } from './helpers';

export const dateFilters = () => {
  const ID_OF_INPUT_ERROR_ELEMENT = 'filter-dates-error';

  let abortController: AbortController;

  const initialize = (fetchAndUpdateResultsFunction: (updatedParams: URLSearchParams) => void) => {
    cleanup();

    const dateFromElement = document.getElementById('date-from') as HTMLInputElement;
    const dateToElement = document.getElementById('date-to') as HTMLInputElement;

    if (!dateFromElement || !dateToElement) {
      return;
    }

    abortController = new AbortController();
    updateDateElements(dateFromElement, dateToElement);

    [dateFromElement, dateToElement].forEach((dateElement) => {
      dateElement.addEventListener('blur', () => {
        const { name, value: date } = dateElement;

        updateDateElements(dateFromElement, dateToElement);

        const params = isDateInvalid(date) ? getSearchParamsAndDelete(name) : getSearchParamsAndSet(name, date);
        if (isValidPeriod(dateFromElement.value, dateToElement.value)) {
          updateUrl(params, fetchAndUpdateResultsFunction);
        }
      }, { signal: abortController.signal });
    });
  };

  const updateDateElements = (dateFromElement: HTMLInputElement, dateToElement: HTMLInputElement) => {
    updateDateRestrictions(dateFromElement, dateToElement);
    updatePeriodValidity(dateFromElement, dateToElement);
  };

  const updateDateRestrictions = (dateFromElement: HTMLInputElement, dateToElement: HTMLInputElement) => {
    setDateRestriction(dateFromElement, dateToElement.value, 'max');
    setDateRestriction(dateToElement, dateFromElement.value, 'min');
  };

  const setDateRestriction = (dateElement: HTMLInputElement, targetDate: string, attributeName: 'max' | 'min') => {
    if (!isDateInvalid(targetDate)) {
      dateElement.setAttribute(attributeName, targetDate);
      return;
    }

    dateElement.removeAttribute(attributeName);
  };

  const updatePeriodValidity = (dateFromElement: HTMLInputElement, dateToElement: HTMLInputElement) => {
    if (isValidPeriod(dateFromElement.value, dateToElement.value)) {
      markDateElementsAsValid(dateFromElement, dateToElement);
      return;
    }

    markDateElementsAsInvalid(dateFromElement, dateToElement);
  };

  const markDateElementsAsInvalid = (dateFromElement: HTMLInputElement, dateToElement: HTMLInputElement) => {
    showInputError();
    markDateElementAsInvalid(dateFromElement);
    markDateElementAsInvalid(dateToElement);
  };

  const markDateElementsAsValid = (dateFromElement: HTMLInputElement, dateToElement: HTMLInputElement) => {
    hideInputError();
    markDateElementAsValid(dateFromElement);
    markDateElementAsValid(dateToElement);
  };

  const markDateElementAsInvalid = (dateElement: HTMLInputElement) => {
    dateElement.setAttribute('aria-describedby', [dateElement.getAttribute('aria-describedby') ?? '', ID_OF_INPUT_ERROR_ELEMENT].join(' ')
      .trim());
    dateElement.setAttribute('aria-invalid', 'true');
    dateElement.classList.add('woo-input-text--invalid');
  };

  const markDateElementAsValid = (dateElement: HTMLInputElement) => {
    dateElement.setAttribute('aria-describedby', dateElement.getAttribute('aria-describedby')?.replace(ID_OF_INPUT_ERROR_ELEMENT, '')
      .trim() ?? '');
    dateElement.setAttribute('aria-invalid', 'false');
    dateElement.classList.remove('woo-input-text--invalid');
  };

  const hideInputError = () => {
    hideElement(getInputErrorElement());
  };

  const showInputError = () => {
    showElement(getInputErrorElement());
  };

  const getInputErrorElement = () => document.getElementById(ID_OF_INPUT_ERROR_ELEMENT) ?? null;

  const isValidPeriod = (dateFrom: string, dateTo: string) => {
    if (isDateInvalid(dateFrom) || isDateInvalid(dateTo)) {
      return true;
    }

    if (dateFrom === '' || dateTo === '') {
      return true;
    }

    if (dateFrom === dateTo) {
      return true;
    }

    return isAfter(new Date(dateTo), new Date(dateFrom));
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
