import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { getLocation } from '../utils/browser';
import { clickableRows } from './clickable-row';

vi.mock('../utils/browser');

describe('the "clickableRows" function', () => {
  const getRows = () => Array.from(document.querySelectorAll('tr'));
  const getClickableRow = () => getRows()[0];
  const getNonClickableRow = () => getRows()[1];
  const clickInsideTableRow = (tableRowElement: HTMLElement) => {
    (
      tableRowElement.querySelector('.inside-table-row') as HTMLElement
    )?.click();
  };

  let cleanup: () => void;
  let initialize: () => void;

  beforeEach(() => {
    document.body.innerHTML = `
      <table>
        <tr>
          <td><a class="js-bhr-clickable-row-link" href="https://mocked-domain.com">Link</a></td>
          <td class="inside-table-row">Clickable row content</td>
        </tr>
        <tr>
          <td>Non-clickable row content</td>
          <td class="inside-table-row">More non-clickable row content</td>
        </tr>
      </table>
    `;

    ({ cleanup, initialize } = clickableRows());
    initialize();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  describe('table rows containing an element with the "js-bhr-clickable-row-link" class name', () => {
    test('should give the table row a color when hovering', () => {
      expect(getClickableRow().classList.contains('bhr-clickable-row')).toBe(
        true,
      );
    });

    test('should navigate to the url of the anchor when clicking (inside) the row', () => {
      expect(getLocation().assign).not.toHaveBeenCalled();

      clickInsideTableRow(getClickableRow());
      expect(getLocation().assign).toHaveBeenCalledTimes(1);
      expect(getLocation().assign).toHaveBeenCalledWith(
        'https://mocked-domain.com/',
      );
    });

    test('should remove event listeners when invoking the "cleanup" function', () => {
      cleanup();

      clickInsideTableRow(getClickableRow());
      expect(getLocation().assign).not.toHaveBeenCalled();
    });
  });

  describe('regular table rows', () => {
    test('should not have a color when hovering', () => {
      expect(getNonClickableRow().classList.contains('bhr-clickable-row')).toBe(
        false,
      );
    });

    test('should do nothing special when clicking (inside) the table row', () => {
      clickInsideTableRow(getNonClickableRow());
      expect(getLocation().assign).not.toHaveBeenCalled();
    });
  });
});
