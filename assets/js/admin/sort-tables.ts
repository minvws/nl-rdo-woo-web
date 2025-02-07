export const sortTables = () => {
  let abortController: AbortController;

  const initialize = () => {
    cleanup();

    // Collect sort buttons.
    const sortTableElements: NodeListOf<HTMLAnchorElement> =
      document.querySelectorAll('.js-sort-table-toggler');

    // Return as soon as possible.
    if (sortTableElements.length === 0) {
      return;
    }

    // Initialize abort controller.
    abortController = new AbortController();

    // Loop the sort toggler elements.
    sortTableElements.forEach((sortToggleElement) => {
      // Attach click handler.
      sortToggleElement.addEventListener(
        'click',
        async (event) => {
          event.preventDefault();

          // Find find the closest ancestor <th> element
          const closestTh = sortToggleElement.closest('th');

          if (closestTh) {
            // Find the index of the clicked column
            const columnIndex = Array.from(
              closestTh.parentElement!.children,
            ).indexOf(closestTh);

            // Get the sort direction.
            let sortDirection = closestTh.getAttribute('aria-sort') || 'asc';

            // Sort results based on the clicked anchor its column index.
            const table: HTMLTableElement = sortToggleElement.closest('table')!;
            sortTable(table, columnIndex, sortDirection!);

            // Change the sort direction as soon as the user has clicked on it.
            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            closestTh.setAttribute('aria-sort', sortDirection);
          }
        },
        { signal: abortController.signal! },
      );
    });
  };

  const sortTable = (
    table: HTMLTableElement,
    columnIndex: number,
    sortDirection: string,
  ) => {
    const tableBody = table.tBodies[0];
    const { rows } = table.tBodies[0];
    const sortedRows = Array.from(rows);

    sortedRows.sort((a, b) => {
      const aValue = a.cells[columnIndex].textContent || '';
      const bValue = b.cells[columnIndex].textContent || '';

      // Perform string comparison based on sortDirection
      if (sortDirection === 'asc') {
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
