import {
  MockInstance,
  afterEach,
  beforeEach,
  describe,
  expect,
  test,
  vi,
} from 'vitest';
import { checkboxFilters } from './checkbox-filters';
import { getUpdatedParamsFromCheckboxFilter, updateUrl } from './helpers';

vi.mock('./helpers', async (importOriginal) => {
  const original = await importOriginal<typeof import('./helpers')>();
  return {
    ...original,
    getUpdatedParamsFromCheckboxFilter: vi
      .fn()
      .mockImplementation(() => new URLSearchParams('?updated=params')),
    updateUrl: vi.fn(),
  };
});

describe('The "checkboxFilters" function', () => {
  const { cleanup, initialize } = checkboxFilters();
  let mockedFetchAndUpdateResultsFunction: MockInstance;

  beforeEach(() => {
    document.body.innerHTML = `
      <input class="js-search-filter-checkbox" type="checkbox" name="mocked_name" value="mocked_value">
    `;

    mockedFetchAndUpdateResultsFunction = vi.fn();
    initialize(mockedFetchAndUpdateResultsFunction as any);
  });

  afterEach(() => {
    cleanup();
    vi.clearAllMocks();
  });

  const getCheckboxElement = () =>
    document.querySelector('.js-search-filter-checkbox') as HTMLInputElement;

  const updateCheckboxValue = (
    checkboxElement: HTMLInputElement = getCheckboxElement(),
  ) => {
    checkboxElement.checked = !checkboxElement.checked;
    checkboxElement.dispatchEvent(new Event('change'));
  };

  test('should update the url when the value of a targeted checkbox changes', () => {
    const checkboxElement = getCheckboxElement();
    expect(updateUrl).not.toHaveBeenCalled();

    updateCheckboxValue(checkboxElement);

    expect(getUpdatedParamsFromCheckboxFilter).toHaveBeenCalledWith(
      checkboxElement,
      checkboxElement.checked,
    );
    expect(updateUrl).toHaveBeenCalledWith(
      new URLSearchParams('?updated=params'),
      mockedFetchAndUpdateResultsFunction,
    );
  });

  test('should not update the url when this functionality gets cleaned up', () => {
    cleanup();
    updateCheckboxValue();

    expect(updateUrl).not.toHaveBeenCalled();
  });
});
