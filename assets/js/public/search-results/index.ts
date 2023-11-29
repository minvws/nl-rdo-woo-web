import { activeFilterPills } from './active-filter-pills';
import { checkboxFilters } from './checkbox-filters';
import { collapsibleFilters } from './collapsible-filters';
import { dateFilters } from './date-filters';
import { fetchAndUpdateResults } from './fetch-and-update-results';
import { getSearchParams } from './helpers';
import { resetFocus } from './reset-focus';

export const searchResults = () => {
  let abortController: AbortController;

  const { initialize: initializeActiveFilterPills, cleanup: cleanupActiveFilterPills } = activeFilterPills();
  const { initialize: initializeCheckboxFilters, cleanup: cleanupCheckboxFilters } = checkboxFilters();
  const { initialize: initializeCollapsibleFilters, cleanup: cleanupCollapsibleFilters } = collapsibleFilters();
  const { initialize: initializeDateFilters, cleanup: cleanupDateFilters } = dateFilters();
  const { initialize: initializeResetFocus, cleanup: cleanupResetFocus } = resetFocus();

  const executeFetchAndUpdateResults = (params: URLSearchParams) => {
    fetchAndUpdateResults(params, (previousActiveElement?: HTMLElement) => {
      initialize(previousActiveElement);
    });
  };

  const initialize = (previousActiveElement?: HTMLElement) => {
    initializeResetFocus(previousActiveElement);

    initializeCollapsibleFilters();
    initializeActiveFilterPills(executeFetchAndUpdateResults);
    initializeCheckboxFilters(executeFetchAndUpdateResults);
    initializeDateFilters(executeFetchAndUpdateResults);

    listenToUrlChanges();
  };

  const listenToUrlChanges = () => {
    abortController = new AbortController();

    window.addEventListener('popstate', () => {
      executeFetchAndUpdateResults(getSearchParams());
    }, { signal: abortController.signal });
  };

  const cleanup = () => {
    if (abortController) {
      abortController.abort();
    }

    cleanupActiveFilterPills();
    cleanupCheckboxFilters();
    cleanupCollapsibleFilters();
    cleanupDateFilters();
    cleanupResetFocus();
  };

  return {
    cleanup,
    initialize,
  };
};
