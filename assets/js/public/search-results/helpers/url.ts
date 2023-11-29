import { getSearchParams, resetPageNumber } from './params';

export const updateUrl = (
  params: URLSearchParams,
  fetchAndUpdateResultsFunction: (updatedParams: URLSearchParams) => void,
  shouldResetPageNumber = true,
) => {
  const updatedParams = shouldResetPageNumber ? resetPageNumber(params) : params;

  const currentParams = getSearchParams();
  if (updatedParams.toString() === currentParams.toString()) {
    return;
  }

  window.history.pushState({}, '', `${window.location.pathname}?${updatedParams}`);

  if (fetchAndUpdateResultsFunction) {
    fetchAndUpdateResultsFunction(updatedParams);
  }
};
