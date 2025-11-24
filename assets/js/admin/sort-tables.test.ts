import { afterEach, beforeEach, describe, expect, test } from 'vitest';
import { sortTables } from './sort-tables';

describe('the "sortTables" function', () => {
  let cleanup: () => void;
  let initialize: () => void;

  beforeEach(() => {
    document.body.innerHTML = `
      <table>
        <thead>
          <tr>
            <th aria-sort="none">
              <a class="js-sort-table-toggler" href="">
                <span class="sr-only js-sort-next-direction" data-text-asc="Sort ascending" data-text-desc="Sort descending">Sort descending</span><span class="sr-only">:</span>
                Number values

                <span class="js-sort-icon js-sort-icon-descending">DESC ICON</span>
                <span class="js-sort-icon js-sort-icon-ascending">ASC ICON</span>
                <span class="js-sort-icon js-sort-icon-none">NO DIRECTION ICON</span>
              </a>
            </th>
            <th aria-sort="none">
              <a class="js-sort-table-toggler" href="">
                Text values
              </a>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>2</td>
            <td>C</td>
          </td>
          <tr>
            <td>1</td>
            <td>B</td>
          </td>
          <tr>
            <td>3</td>
            <td>A</td>
          </td>
        </tbody>
      </table>
    `;

    ({ cleanup, initialize } = sortTables());
    initialize();
  });

  afterEach(() => {
    cleanup();
  });

  const getAriaSortDirection = (columnNumber: number) =>
    document
      .querySelectorAll<HTMLAnchorElement>('thead th')
      [columnNumber - 1]?.getAttribute('aria-sort');

  const getToggler = (columnNumber: number) =>
    document.querySelectorAll<HTMLAnchorElement>('.js-sort-table-toggler')[
      columnNumber - 1
    ];

  const getTogglerText = (columnNumber: number) =>
    getToggler(columnNumber)?.textContent?.trim()?.replaceAll(/\s+/g, ' ');

  const sort = (columnNumber: number) => getToggler(columnNumber)?.click();

  const getBodyCellText = (rowNumber: number, cellNumber: number) =>
    document.querySelectorAll('tbody tr')[rowNumber - 1]?.children[
      cellNumber - 1
    ]?.textContent;

  test('should sort the table when a sort element is clicked', () => {
    sort(1);

    expect(getBodyCellText(1, 1)).toBe('1');
    expect(getBodyCellText(2, 1)).toBe('2');
    expect(getBodyCellText(3, 1)).toBe('3');

    sort(1);
    expect(getBodyCellText(1, 1)).toBe('3');
    expect(getBodyCellText(2, 1)).toBe('2');
    expect(getBodyCellText(3, 1)).toBe('1');

    sort(2);
    expect(getBodyCellText(1, 2)).toBe('A');
    expect(getBodyCellText(2, 2)).toBe('B');
    expect(getBodyCellText(3, 2)).toBe('C');
  });

  test('should set the sorting direction in a aria attribute', () => {
    sort(1);
    expect(getAriaSortDirection(1)).toBe('ascending');

    sort(1);
    expect(getAriaSortDirection(1)).toBe('descending');
  });

  test('should reset the sorting direction of the other sortable columns', () => {
    sort(1);
    expect(getAriaSortDirection(1)).toBe('ascending');
    expect(getAriaSortDirection(2)).toBe('none');

    sort(2);
    expect(getAriaSortDirection(1)).toBe('none');
    expect(getAriaSortDirection(2)).toBe('ascending');
  });

  test('should update the sort direction of the element which is read by screen readers', () => {
    sort(1);
    expect(getTogglerText(1)).toContain('Sort descending: Number values');

    sort(1);
    expect(getTogglerText(1)).toContain('Sort ascending: Number values');

    sort(1);
    expect(getTogglerText(1)).toContain('Sort descending: Number values');
  });

  test('should display the correct icon', () => {
    sort(1);
    expect(getTogglerText(1)).toContain('DESC ICON');

    sort(1);
    expect(getTogglerText(1)).toContain('ASC ICON');

    sort(2);
    expect(getTogglerText(1)).toContain('NO DIRECTION ICON');
  });
});
