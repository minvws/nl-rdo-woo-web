import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { fetchAndUpdateResults } from './fetch-and-update-results';

describe('The functionality regarding fetching and updating search results', () => {
  const getMockedParams = () => new URLSearchParams('q=test&mock=true');

  beforeEach(() => {
    document.body.innerHTML = `
      <div id="js-search-filters" data-url="https://example.com"></div>
      <div id="js-search-results"></div>
      <button>Mocked button</button>
    `;

    global.fetch = vi.fn().mockImplementation(() =>
      Promise.resolve({
        text: () =>
          Promise.resolve(
            JSON.stringify({
              facets: JSON.stringify('<div>New filters from response</div>'),
              results: JSON.stringify('<div>New results from response</div>'),
            }),
          ),
      } as Response),
    );
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  const getButtonElement = () => document.querySelector('button');
  const getSearchFiltersElement = () =>
    document.getElementById('js-search-filters');
  const getSearchResultsElement = () =>
    document.getElementById('js-search-results');

  it('should make a fetch request to the filters element data url with the search provided params', () => {
    const mockedParams = getMockedParams();
    fetchAndUpdateResults(mockedParams);

    expect(global.fetch).toHaveBeenCalledWith(
      `https://example.com?${mockedParams}`,
    );
  });

  it('should do nothing if the filters or search results element is not found', () => {
    document.body.innerHTML = '';
    fetchAndUpdateResults(new URLSearchParams());

    expect(global.fetch).not.toHaveBeenCalled();
  });

  it('should update the filters and results elements with the fetched data', async () => {
    const mockedParams = getMockedParams();
    await fetchAndUpdateResults(mockedParams);

    expect(getSearchFiltersElement()?.innerHTML).toBe(
      '<div>New filters from response</div>',
    );
    expect(getSearchResultsElement()?.innerHTML).toBe(
      '<div>New results from response</div>',
    );
  });

  it('should return the previous active element', async () => {
    getButtonElement()?.focus();

    const mockedParams = getMockedParams();
    let previousActiveElement = await fetchAndUpdateResults(mockedParams);

    expect(previousActiveElement).toBe(getButtonElement());

    Object.defineProperty(document, 'activeElement', {
      value: null,
      writable: true,
    });

    previousActiveElement = await fetchAndUpdateResults(mockedParams);

    expect(previousActiveElement).toBe(null);
  });

  it('should not throw an error if the response is not valid JSON', async () => {
    global.fetch = vi.fn().mockRejectedValue(new Error('Invalid JSON'));

    expect(async () => {
      await fetchAndUpdateResults(getMockedParams());
    }).not.toThrow();
  });
});
