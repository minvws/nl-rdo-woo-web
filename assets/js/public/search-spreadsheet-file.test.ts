import { fireEvent, screen } from '@testing-library/dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { isElementVisible } from '@utils';
import { searchSpreadsheetFile } from './search-spreadsheet-file';

vi.mock('../utils/browser', () => ({
  getWindow: () => ({
    matchMedia: () => ({ matches: true }), // disables animation
  }),
}));

describe('The functionality regarding searching in a spreadsheet file', () => {
  let cleanup: () => void;

  beforeEach(() => {
    vi.useFakeTimers();

    document.body.innerHTML = `
      <div class="js-search-spreadsheet-file">
        <form role="search" class="hidden" data-testid="form">
          <input type="search" name="q" data-testid="input" />
        </form>
        <output
          data-testid="output"
          data-message-none='Geen rijen gevonden voor de zoekterm "{term}"'
          data-message-shown='{count} van de {total} rijen getoond voor de zoekterm "{term}"'
        ></output>
        <table>
          <tbody>
            <tr data-testid="row"><td>Appel</td><td>Rood</td></tr>
            <tr data-testid="row"><td>Peer</td><td>Groen</td></tr>
            <tr data-testid="row"><td>Appelmoes</td><td>Bruin</td></tr>
          </tbody>
        </table>
      </div>
    `;

    const functionality = searchSpreadsheetFile();
    cleanup = functionality.cleanup;
    functionality.initialize();
  });

  afterEach(() => {
    cleanup();
    document.body.innerHTML = '';
    vi.useRealTimers();
  });

  const getOutput = () => screen.getByTestId('output');
  const getRows = () => screen.getAllByTestId('row');
  const getVisibleRows = () => getRows().filter((row) => !row.hidden);

  const search = async (value: string) => {
    fireEvent.input(screen.getByTestId('input'), { target: { value } });
    await vi.advanceTimersByTimeAsync(300);
  };

  it('should keep the search form hidden for half a second after initializing', async () => {
    await vi.advanceTimersByTimeAsync(499);

    expect(isElementVisible(screen.getByTestId('form'))).toBe(false);
  });

  it('should show the search form after half a second', async () => {
    await vi.advanceTimersByTimeAsync(500);

    expect(isElementVisible(screen.getByTestId('form'))).toBe(true);
  });

  it('should hide rows that do not match the search term', async () => {
    await search('appel');

    expect(getVisibleRows()).toHaveLength(2);
    expect(getRows()[1]).toHaveProperty('hidden', true);
  });

  it('should report the number of rows found', async () => {
    await search('appel');

    expect(getOutput()).toHaveTextContent(
      '2 van de 3 rijen getoond voor de zoekterm "appel"',
    );
  });

  it('should report a single row with the same message', async () => {
    await search('peer');

    expect(getOutput()).toHaveTextContent(
      '1 van de 3 rijen getoond voor de zoekterm "peer"',
    );
  });

  it('should report when no rows are found', async () => {
    await search('banaan');

    expect(getOutput()).toHaveTextContent(
      'Geen rijen gevonden voor de zoekterm "banaan"',
    );
    expect(getVisibleRows()).toHaveLength(0);
  });

  it('should not filter when the search term has 2 characters or fewer', async () => {
    await search('pe');

    expect(getVisibleRows()).toHaveLength(3);
    expect(getOutput()).toBeEmptyDOMElement();
  });

  it('should show all rows again when the search term is cleared', async () => {
    await search('peer');
    await search('');

    expect(getVisibleRows()).toHaveLength(3);
    expect(getOutput()).toBeEmptyDOMElement();
  });

  it('should not filter before the debounce delay has passed', () => {
    fireEvent.input(screen.getByTestId('input'), { target: { value: 'peer' } });

    expect(getVisibleRows()).toHaveLength(3);
  });
});
