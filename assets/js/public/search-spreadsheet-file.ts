import { collapseElement, debounce, expandElement } from '@utils';

const MINIMUM_SEARCH_TERM_LENGTH = 3;
const DEBOUNCE_DELAY_IN_MS = 300;
const EXPAND_DELAY_IN_MS = 500;

export const searchSpreadsheetFile = () => {
  let abortController: AbortController;
  let expandTimeout: NodeJS.Timeout;
  let container: HTMLElement | null;
  let form: HTMLFormElement | null;
  let input: HTMLInputElement | null;
  let output: HTMLOutputElement | null;
  let rows: HTMLTableRowElement[];

  const initialize = () => {
    container = document.querySelector('.js-search-spreadsheet-file');
    form = container?.querySelector('form') ?? null;
    input = container?.querySelector('input[type="search"]') ?? null;
    output = container?.querySelector('output') ?? null;

    if (!container || !form || !input || !output) {
      return;
    }

    collapseElement(form, false);

    const formToExpand = form;
    expandTimeout = setTimeout(
      () => expandElement(formToExpand),
      EXPAND_DELAY_IN_MS,
    );

    rows = Array.from(container.querySelectorAll('tbody tr'));
    abortController = new AbortController();

    const debouncedFilter = debounce(filterRows, DEBOUNCE_DELAY_IN_MS);

    input.addEventListener('input', () => debouncedFilter(), {
      signal: abortController.signal,
    });

    form.addEventListener(
      'submit',
      (event) => {
        event.preventDefault();
        filterRows();
      },
      { signal: abortController.signal },
    );
  };

  const getSearchTerm = () => input?.value.trim() ?? '';

  const filterRows = () => {
    const searchTerm = getSearchTerm();

    if (searchTerm.length < MINIMUM_SEARCH_TERM_LENGTH) {
      showAllRows();
      return;
    }

    const lowerCaseSearchTerm = searchTerm.toLowerCase();
    let matchCount = 0;

    rows.forEach((row) => {
      const isMatch = (row.textContent ?? '')
        .toLowerCase()
        .includes(lowerCaseSearchTerm);

      setRowVisibility(row, isMatch);

      if (isMatch) {
        matchCount++;
      }
    });

    showResultMessage(matchCount, searchTerm);
  };

  const setRowVisibility = (row: HTMLTableRowElement, isVisible: boolean) => {
    row.toggleAttribute('hidden', !isVisible);
  };

  const showAllRows = () => {
    rows.forEach((row) => {
      setRowVisibility(row, true);
    });

    showMessage('');
  };

  const showResultMessage = (matchCount: number, searchTerm: string) => {
    const message = getMessageTemplate(matchCount)
      .replace('{count}', String(matchCount))
      .replace('{total}', String(rows.length))
      .replace('{term}', searchTerm);

    showMessage(message);
  };

  const getMessageTemplate = (matchCount: number) => {
    const { messageNone, messageShown } = output?.dataset ?? {};

    return (matchCount === 0 ? messageNone : messageShown) ?? '';
  };

  const showMessage = (message: string) => {
    if (output) {
      output.textContent = message;
    }
  };

  const cleanup = () => {
    clearTimeout(expandTimeout);
    abortController?.abort();
  };

  return {
    cleanup,
    initialize,
  };
};
