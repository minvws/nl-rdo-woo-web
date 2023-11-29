export const fetchAndUpdateResults = (params: URLSearchParams, callbackFunction: (previousActiveElement?: HTMLElement) => void) => {
  const filtersElement = document.getElementById('js-search-filters');
  const resultsElement = document.getElementById('js-search-results');

  if (!filtersElement || !resultsElement) {
    return;
  }

  fetch(`/_result?${params}`)
    .then((response) => response.text())
    .then((json) => {
      const data = JSON.parse(json);
      const { activeElement: previousActiveElement } = document;

      filtersElement.innerHTML = JSON.parse(data.facets);
      resultsElement.innerHTML = JSON.parse(data.results);

      if (callbackFunction) {
        callbackFunction((previousActiveElement as HTMLElement) || undefined);
      }
    });
};
