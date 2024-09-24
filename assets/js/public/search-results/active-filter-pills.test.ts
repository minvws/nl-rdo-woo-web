import { MockInstance, afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { activeFilterPills } from './active-filter-pills';
import { updateUrl } from './helpers';

let mockedCheckboxFilterElements: HTMLInputElement[];

vi.mock('./helpers', async (importOriginal) => {
  const original = await importOriginal<typeof import('./helpers')>();
  return {
    ...original,
    getUpdatedParamsFromCheckboxFilter: vi.fn().mockImplementation(() => new URLSearchParams('?updated=params')),
    getCheckboxFilterElements: vi.fn().mockImplementation(() => mockedCheckboxFilterElements),
    updateUrl: vi.fn(),
  };
});

describe('The "activeFilterPills" function', () => {
  const { cleanup, initialize } = activeFilterPills();
  let mockedFetchAndUpdateResultsFunction: MockInstance;

  const createMockedCheckboxFilterElement = (name: string, value: string) => {
    const mockedCheckboxFilterElement = document.createElement('input');
    mockedCheckboxFilterElement.type = 'checkbox';
    mockedCheckboxFilterElement.name = name;
    mockedCheckboxFilterElement.value = value;
    return mockedCheckboxFilterElement;
  };

  beforeEach(() => {
    document.body.innerHTML = `
      <ul>
        <li><a class="js-active-filter-pill" href="" data-key="mocked_key_1" data-value="mocked_value_1">Mocked filter 1</a></li>
        <li><a class="js-active-filter-pill" href="" data-key="mocked_key_2" data-value="mocked_value_2">Mocked filter 2</a></li>
        <li><a class="js-active-filter-pill" href="" data-key="mocked_key_3" data-value="mocked_value_3">Mocked filter 3</a></li>
      </ul>
    `;

    mockedCheckboxFilterElements = [
      createMockedCheckboxFilterElement('mocked_key_1', 'mocked_value_1'),
    ];
    mockedFetchAndUpdateResultsFunction = vi.fn();
  });

  afterEach(() => {
    cleanup();
    vi.clearAllMocks();
  });

  const initializeCheckboxFilters = () => initialize(mockedFetchAndUpdateResultsFunction as any);

  const getActiveFilterPills = () => document.querySelectorAll<HTMLAnchorElement>('.js-active-filter-pill');
  const clickActiveFilterPill = (index = 0) => getActiveFilterPills()[index].click();
  const hasListElement = () => document.querySelector('ul') !== null;

  describe('when an active filter pill is clicked', () => {
    test('should update the url', () => {
      initializeCheckboxFilters();
      expect(updateUrl).not.toHaveBeenCalled();

      clickActiveFilterPill();

      expect(updateUrl).toHaveBeenCalledWith(new URLSearchParams('?updated=params'), mockedFetchAndUpdateResultsFunction);
    });

    test('should remove the clicked filter pill and the surrounding list when no active filter pills are left', () => {
      document.body.innerHTML = `
        <ul>
          <li><a class="js-active-filter-pill" href="" data-key="mocked_key_1" data-value="mocked_value_1">Mocked filter 1</a></li>
        </ul>
      `;
      mockedCheckboxFilterElements = [];

      initializeCheckboxFilters();
      expect(hasListElement()).toBe(true);

      clickActiveFilterPill();

      expect(hasListElement()).toBe(false);
    });
  });
});
