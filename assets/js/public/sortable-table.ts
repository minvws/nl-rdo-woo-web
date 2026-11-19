import { createPublicIcon } from './utils';

const BUTTON_CLASS_NAME = 'woo-fill-cell focus:-outline-offset-2';

type SortDirection = 'ascending' | 'descending';
type SortState = SortDirection | 'none';

const ICON_NAME_BY_SORT_STATE: Record<SortState, string> = {
  ascending: 'sort-up',
  descending: 'sort-down',
  none: 'sort',
};

const collator = new Intl.Collator(undefined, { numeric: true });

export const sortableTable = () => {
  let abortController: AbortController;

  const initialize = () => {
    abortController = new AbortController();

    document
      .querySelectorAll<HTMLTableElement>('.js-sortable-table')
      .forEach((table) => {
        if (!hasMultipleRows(table)) {
          return;
        }

        table
          .querySelectorAll<HTMLTableCellElement>('.js-sortable-column')
          .forEach((column) => {
            const button = wrapInButton(column);
            setSortState(column, 'none');

            button.addEventListener(
              'click',
              () => sortByColumn(table, column),
              {
                signal: abortController.signal,
              },
            );
          });
      });
  };

  const hasMultipleRows = (table: HTMLTableElement) =>
    table.querySelectorAll('tbody tr').length > 1;

  const wrapInButton = (column: HTMLTableCellElement) => {
    const button = document.createElement('button');
    button.className = BUTTON_CLASS_NAME;
    button.type = 'button';

    const message = document.createElement('span');
    message.className = 'sr-only';
    button.append(message, ' ', ...column.childNodes);

    column.append(button);

    return button;
  };

  const setSortState = (column: HTMLTableCellElement, sortState: SortState) => {
    column.setAttribute('aria-sort', sortState);

    const button = column.querySelector('button');
    const message = button?.querySelector('.sr-only');

    if (message) {
      message.textContent = getNextSortMessage(column, sortState);
    }

    button?.querySelector('svg')?.remove();
    button?.insertAdjacentHTML(
      'beforeend',
      createPublicIcon({
        color: 'fill-black',
        name: ICON_NAME_BY_SORT_STATE[sortState],
      }),
    );
  };

  const getNextSortMessage = (
    column: HTMLTableCellElement,
    sortState: SortState,
  ) => {
    const { messageSortAsc, messageSortDesc } =
      column.closest<HTMLTableElement>('.js-sortable-table')?.dataset ?? {};

    const nextSortMessage =
      sortState === 'ascending' ? messageSortDesc : messageSortAsc;

    return nextSortMessage ? `${nextSortMessage}:` : '';
  };

  const sortByColumn = (
    table: HTMLTableElement,
    column: HTMLTableCellElement,
  ) => {
    const direction: SortDirection =
      column.getAttribute('aria-sort') === 'ascending'
        ? 'descending'
        : 'ascending';

    table
      .querySelectorAll<HTMLTableCellElement>('.js-sortable-column')
      .forEach((sortableColumn) => {
        setSortState(sortableColumn, 'none');
      });
    setSortState(column, direction);

    sortRows(table, column.cellIndex, direction);
  };

  const sortRows = (
    table: HTMLTableElement,
    columnIndex: number,
    direction: SortDirection,
  ) => {
    const tbody = table.querySelector('tbody');

    if (!tbody) {
      return;
    }

    const getCellText = (row: HTMLTableRowElement) =>
      row.cells[columnIndex]?.textContent?.trim() ?? '';
    const directionFactor = direction === 'ascending' ? 1 : -1;

    const sortedRows = Array.from(tbody.rows).sort(
      (rowA, rowB) =>
        collator.compare(getCellText(rowA), getCellText(rowB)) *
        directionFactor,
    );

    tbody.append(...sortedRows);
  };

  const cleanup = () => {
    abortController?.abort();
  };

  return {
    cleanup,
    initialize,
  };
};
