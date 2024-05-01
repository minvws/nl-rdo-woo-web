import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { getLocation } from '../utils/browser';
import { clickableRows } from './clickable-row';

vi.mock('../utils/browser');

describe('the "clickableRows" function', () => {
  const getRows = () => Array.from(document.querySelectorAll('tr'));
  const getClickableRowWithAnchor = () => getRows()[0];
  const getClickableRowWithButton = () => getRows()[1];
  const getNonClickableRow = () => getRows()[2];
  const clickInsideTableRow = (tableRowElement: HTMLElement) => {
    (tableRowElement.querySelector('.inside-table-row') as HTMLElement)?.click();
  };
  const clickInsideClickableRowWithAnchor = () => clickInsideTableRow(getClickableRowWithAnchor());
  const clickInsideClickableRowWithButton = () => clickInsideTableRow(getClickableRowWithButton());
  const clickInsideNonClickableRow = () => clickInsideTableRow(getNonClickableRow());

  let cleanup: () => void;
  let initialize: () => void;

  beforeEach(() => {
    document.body.innerHTML = `
      <table>
        <tr>
          <td><a class="js-clickable-row__focusable" href="https://mocked-domain.com">Link</a></td>
          <td class="inside-table-row">Clickable row content</td>
        </tr>
        <tr>
          <td><button class="js-clickable-row__focusable">Some button</button></td>
          <td class="inside-table-row">Clickable row content</td>
        </tr>
        <tr>
          <td><a href="https://another-mocked-domain.com">Link</a></td>
          <td class="inside-table-row">Non-clickable row content</td>
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

  describe('table rows containing an element with the "js-clickable-row__focusable" class name', () => {
    test('should give the table row a color when hovering', () => {
      expect(getClickableRowWithButton().classList.contains('bhr-clickable-row')).toBe(true);
    });

    describe('if the focusable element is an anchor', () => {
      test('should navigate to the url of the anchor when clicking (inside) the row', () => {
        expect(getLocation().assign).not.toHaveBeenCalled();

        clickInsideClickableRowWithAnchor();
        expect(getLocation().assign).toHaveBeenCalledTimes(1);
        expect(getLocation().assign).toHaveBeenCalledWith('https://mocked-domain.com/');
      });
    });

    test('should have a color when the focusable element within the row receives focus', () => {
      const clickableRow = getClickableRowWithButton();
      const buttonElement = clickableRow.querySelector('.js-clickable-row__focusable') as HTMLButtonElement;

      expect(clickableRow.classList.contains('bhr-clickable-row--with-color')).toBe(false);
      buttonElement.focus();
      expect(clickableRow.classList.contains('bhr-clickable-row--with-color')).toBe(true);
    });

    test('should remove event listeners when invoking the "cleanup" function', () => {
      cleanup();

      clickInsideClickableRowWithButton();
      expect(getLocation().assign).not.toHaveBeenCalled();
    });
  });

  describe('regular table rows', () => {
    test('should not have a color when hovering', () => {
      expect(getNonClickableRow().classList.contains('bhr-clickable-row')).toBe(false);
    });

    test('should do nothing special when clicking (inside) the table row', () => {
      clickInsideNonClickableRow();
      expect(getLocation().assign).not.toHaveBeenCalled();
    });
  });
});
