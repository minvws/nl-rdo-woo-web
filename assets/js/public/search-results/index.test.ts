import {
  MockInstance,
  afterEach,
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';
import { searchResults } from '.';
import { fetchAndUpdateResults } from './fetch-and-update-results';
import { getSearchParams } from './helpers';

let activeFilterPillsCleanupSpy: MockInstance;
let activeFilterPillsInitializeSpy: MockInstance;
let checkboxFiltersCleanupSpy: MockInstance;
let checkboxFiltersInitializeSpy: MockInstance;
let collapsibleFiltersCleanupSpy: MockInstance;
let collapsibleFiltersInitializeSpy: MockInstance;
let dateFiltersCleanupSpy: MockInstance;
let dateFiltersInitializeSpy: MockInstance;
let resetFocusCleanupSpy: MockInstance;
let resetFocusInitializeSpy: MockInstance;

vi.mock('./fetch-and-update-results', () => ({
  fetchAndUpdateResults: vi.fn(),
}));

describe('The search results function', () => {
  let initialize: () => void;
  let cleanup: () => void;

  vi.mock('./active-filter-pills', () => ({
    activeFilterPills: () => ({
      cleanup: activeFilterPillsCleanupSpy,
      initialize: activeFilterPillsInitializeSpy,
    }),
  }));

  vi.mock('./checkbox-filters', () => ({
    checkboxFilters: () => ({
      cleanup: checkboxFiltersCleanupSpy,
      initialize: checkboxFiltersInitializeSpy,
    }),
  }));

  vi.mock('./collapsible-filters', () => ({
    collapsibleFilters: () => ({
      cleanup: collapsibleFiltersCleanupSpy,
      initialize: collapsibleFiltersInitializeSpy,
    }),
  }));

  vi.mock('./date-filters', () => ({
    dateFilters: () => ({
      cleanup: dateFiltersCleanupSpy,
      initialize: dateFiltersInitializeSpy,
    }),
  }));

  vi.mock('./reset-focus', () => ({
    resetFocus: () => ({
      cleanup: resetFocusCleanupSpy,
      initialize: resetFocusInitializeSpy,
    }),
  }));

  beforeEach(() => {
    activeFilterPillsCleanupSpy = vi.fn();
    activeFilterPillsInitializeSpy = vi.fn();
    checkboxFiltersCleanupSpy = vi.fn();
    checkboxFiltersInitializeSpy = vi.fn();
    collapsibleFiltersCleanupSpy = vi.fn();
    collapsibleFiltersInitializeSpy = vi.fn();
    dateFiltersCleanupSpy = vi.fn();
    dateFiltersInitializeSpy = vi.fn();
    resetFocusCleanupSpy = vi.fn();
    resetFocusInitializeSpy = vi.fn();

    ({ initialize, cleanup } = searchResults());
  });

  afterEach(() => {
    cleanup();
  });

  it('should initialize all necessary functionality regarding the search results', () => {
    expect(resetFocusInitializeSpy).not.toHaveBeenCalled();
    expect(activeFilterPillsInitializeSpy).not.toHaveBeenCalled();
    expect(checkboxFiltersInitializeSpy).not.toHaveBeenCalled();
    expect(collapsibleFiltersInitializeSpy).not.toHaveBeenCalled();
    expect(dateFiltersInitializeSpy).not.toHaveBeenCalled();

    initialize();

    expect(resetFocusInitializeSpy).toHaveBeenCalled();
    expect(activeFilterPillsInitializeSpy).toHaveBeenCalled();
    expect(checkboxFiltersInitializeSpy).toHaveBeenCalled();
    expect(collapsibleFiltersInitializeSpy).toHaveBeenCalled();
    expect(dateFiltersInitializeSpy).toHaveBeenCalled();
  });

  it('should fetch and update the search results when the URL changes', () => {
    initialize();

    expect(fetchAndUpdateResults).not.toHaveBeenCalled();

    window.dispatchEvent(new Event('popstate'));

    expect(fetchAndUpdateResults).toHaveBeenNthCalledWith(1, getSearchParams());
  });
});
