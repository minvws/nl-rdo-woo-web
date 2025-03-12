export const fetchAndUpdateResults = async (params: URLSearchParams) => {
  const filtersElement = document.getElementById('js-search-filters');
  const resultsElement = document.getElementById('js-search-results');

  if (!filtersElement || !resultsElement) {
    return;
  }

  try {
    const response = await fetch(`${filtersElement.dataset.url}?${params}`);
    const json = await response.text();
    const data = JSON.parse(json);
    const { activeElement: previousActiveElement } = document;

    filtersElement.innerHTML = JSON.parse(data.facets);
    resultsElement.innerHTML = JSON.parse(data.results);

    return previousActiveElement as HTMLElement | null;
    // eslint-disable-next-line @typescript-eslint/no-unused-vars, no-empty
  } catch (error) {}
};
