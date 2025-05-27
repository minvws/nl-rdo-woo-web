export const sortTables = () => {
  let abortController: AbortController;

  const TOGGLER_SELECTOR = '.js-sort-table-toggler';

  type SortDirection = 'ascending' | 'descending' | 'none';

  const initialize = () => {
    cleanup();

    abortController = new AbortController();

    document
      .querySelectorAll<HTMLAnchorElement>(TOGGLER_SELECTOR)
      .forEach((sortTableTogglerElement) => {
        sortTableTogglerElement.addEventListener(
          'click',
          (event) => {
            event.preventDefault();

            const tableElement =
              sortTableTogglerElement.closest<HTMLTableElement>('table')!;
            const tableColumnElement =
              sortTableTogglerElement.closest<HTMLTableCellElement>('th')!;

            const newSortDirection = getNewSortDirection(tableColumnElement);
            resetTableSortDirections(tableElement);
            sortTable(
              tableElement,
              getIndexOfTableColumn(tableColumnElement),
              newSortDirection,
            );
            setColumnSortDirection(tableColumnElement, newSortDirection);
          },
          { signal: abortController.signal! },
        );
      });
  };

  const getNewSortDirection = (
    tableColumnElement: HTMLTableCellElement,
  ): SortDirection =>
    tableColumnElement.getAttribute('aria-sort') === 'ascending'
      ? 'descending'
      : 'ascending';

  const resetTableSortDirections = (tableElement: HTMLTableElement) => {
    tableElement
      .querySelectorAll<HTMLTableCellElement>('th[aria-sort]')
      .forEach((tableColumnElement) => {
        setColumnSortDirection(tableColumnElement, 'none');
      });
  };

  const setColumnSortDirection = (
    tableColumnElement: HTMLTableCellElement,
    sortDirection: SortDirection,
  ) => {
    tableColumnElement.setAttribute('aria-sort', sortDirection);
    setSortTableTogglerDirection(
      tableColumnElement.querySelector<HTMLAnchorElement>(TOGGLER_SELECTOR),
      sortDirection,
    );
  };

  const setSortTableTogglerDirection = (
    sortTableTogglerElement: HTMLAnchorElement | null,
    sortDirection: SortDirection,
  ) => {
    if (!sortTableTogglerElement) {
      return;
    }

    updateReadbleNextSortingDirection(sortTableTogglerElement, sortDirection);
    updateSortIcon(sortTableTogglerElement, sortDirection);
  };

  const updateReadbleNextSortingDirection = (
    sortTableTogglerElement: HTMLAnchorElement,
    sortDirection: SortDirection,
  ) => {
    const element = sortTableTogglerElement.querySelector<HTMLSpanElement>(
      '.js-sort-next-direction',
    );
    if (!element) {
      return;
    }

    element.textContent =
      sortDirection === 'ascending'
        ? (element.dataset.textDesc ?? '')
        : (element.dataset.textAsc ?? '');
  };

  const updateSortIcon = (
    sortTableTogglerElement: HTMLAnchorElement,
    sortDirection: SortDirection,
  ) => {
    const iconElements =
      sortTableTogglerElement.querySelectorAll<HTMLSpanElement>(
        '.js-sort-icon',
      );

    iconElements.forEach((iconElement) => {
      iconElement.classList.add('hidden');

      if (iconElement.classList.contains(`js-sort-icon-${sortDirection}`)) {
        iconElement.classList.remove('hidden');
      }
    });
  };

  const getIndexOfTableColumn = (tableColumnElement: HTMLTableCellElement) =>
    [...tableColumnElement.parentElement!.children].indexOf(tableColumnElement);

  const sortTable = (
    table: HTMLTableElement,
    columnIndex: number,
    sortDirection: SortDirection,
  ) => {
    const tableBody = table.tBodies[0];

    const sortedRows = [...tableBody.rows].sort((a, b) => {
      const aValue = a.cells[columnIndex].textContent || '';
      const bValue = b.cells[columnIndex].textContent || '';

      if (sortDirection === 'ascending') {
        return aValue.localeCompare(bValue);
      }
      return bValue.localeCompare(aValue);
    });

    // Clear the table body
    tableBody.innerHTML = '';

    // Append the sorted rows to the table body
    sortedRows.forEach((row) => {
      table.tBodies[0].appendChild(row);
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
