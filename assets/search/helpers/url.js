import { getSearchParams, resetPageNumber } from './params';

export const updateUrl = (params, fetchAndUpdateResultsFunction, shouldResetPageNumber = true) => {
    const updatedParams = shouldResetPageNumber ? resetPageNumber(params) : params;

    const currentParams = getSearchParams();
    if (updatedParams.toString() === currentParams.toString()) {
        return;
    }

    window.history.pushState({}, '', `${location.pathname}?${updatedParams}`);

    if (fetchAndUpdateResultsFunction) {
        fetchAndUpdateResultsFunction(updatedParams);
    }
}
