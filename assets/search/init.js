import { onDomReady } from '@minvws/manon/utils';
import { activeFilterPills } from './active-filter-pills';
import { checkboxFilters } from './checkbox-filters';
import { collapsibleFilters } from './collapsible-filters';
import { dateFilters } from './date-filters';
import { fetchAndUpdateResults } from './fetch-and-update-results';
import { getSearchParams } from './helpers';
import { resetFocus } from './reset-focus';

onDomReady(() => {
    const { initialize: initializeActiveFilterPills } = activeFilterPills();
    const { initialize: initializeCheckboxFilters } = checkboxFilters();
    const { initialize: initializeCollapsibleFilters } = collapsibleFilters();
    const { initialize: initializeDateFilters } = dateFilters();
    const { initialize: initializeResetFocus } = resetFocus();

    const executeFetchAndUpdateResults = (params) => {
        fetchAndUpdateResults(params, (previousActiveElement) => {
            initialize(previousActiveElement);
        });
    };

    const initialize = (previousActiveElement) => {
        initializeResetFocus(previousActiveElement);

        initializeCollapsibleFilters();
        initializeActiveFilterPills(executeFetchAndUpdateResults);
        initializeCheckboxFilters(executeFetchAndUpdateResults);
        initializeDateFilters(executeFetchAndUpdateResults);
    };

    const listenAndUnlistenToUrlChanges = () => {
        const controller = new AbortController();

        window.addEventListener('popstate', () => {
            executeFetchAndUpdateResults(getSearchParams());
        }, { signal: controller.signal });

        window.addEventListener('beforeunload', () => {
            controller.abort();
        });
    }

    initialize();
    listenAndUnlistenToUrlChanges();
});
