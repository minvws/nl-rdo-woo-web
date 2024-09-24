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
            <th><a class="js-sort-table-toggler" href="">Column 1</a></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>2</td>
          </td>
          <tr>
            <td>1</td>
          </td>
          <tr>
            <td>3</td>
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

  const getSortedDirection = () => document.querySelector('th')?.getAttribute('aria-sort');
  const sort = () => (document.querySelector('.js-sort-table-toggler') as HTMLAnchorElement)?.click();
  const getBodyCellText = (rowNumber: number) => document.querySelectorAll('td')[rowNumber].textContent;

  test('should sort the table when a sort element is clicked', () => {
    sort();
    expect(getBodyCellText(0)).toBe('1');
    expect(getBodyCellText(1)).toBe('2');
    expect(getBodyCellText(2)).toBe('3');

    sort();
    expect(getBodyCellText(0)).toBe('3');
    expect(getBodyCellText(1)).toBe('2');
    expect(getBodyCellText(2)).toBe('1');
  });

  test('should set the sortng direction in a aria attribute', () => {
    sort();
    expect(getSortedDirection()).toBe('desc');

    sort();
    expect(getSortedDirection()).toBe('asc');
  });
});
