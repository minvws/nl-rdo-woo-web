import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import type { PublicationSearchResult } from './interface';
import SearchResults from './SearchResults.vue';

describe('The "SearchResults" component', () => {
  const createResult = (
    type: PublicationSearchResult['type'],
  ): PublicationSearchResult => ({
    id: `mocked-${type}`,
    link: `mocked-link-${type}`,
    number: null,
    title: `mocked-title-${type}`,
    type,
  });
  const mockedDocumentResults = [createResult('document')];
  const mockedPublicationResults = [createResult('dossier')];

  const createComponent = (
    results = [...mockedDocumentResults, ...mockedPublicationResults],
  ) =>
    mount(SearchResults, {
      props: {
        results,
      },
      shallow: true,
    });

  const getSearchResultsTableComponents = (component: VueWrapper) =>
    component.findAllComponents({ name: 'SearchResultsTable' });
  const findSearchResultsTableComponentByTitle = (
    component: VueWrapper,
    title: string,
  ) =>
    getSearchResultsTableComponents(component).find(
      (tableComponent) => tableComponent.props('title') === title,
    );
  const getDocumentSearchResultsTableComponent = (component: VueWrapper) =>
    findSearchResultsTableComponentByTitle(component, 'Woo-documenten');
  const getPublicationSearchResultsTableComponent = (component: VueWrapper) =>
    findSearchResultsTableComponentByTitle(component, 'Publicaties');

  test('should display the document search results in a separate section', () => {
    const component = createComponent();
    expect(getDocumentSearchResultsTableComponent(component)?.exists()).toBe(
      true,
    );
  });

  test('should display the publication search results in a separate section', () => {
    const component = createComponent();
    expect(getPublicationSearchResultsTableComponent(component)?.exists()).toBe(
      true,
    );
  });

  test('should display a text saying no results where found if no results are provided', async () => {
    const component = createComponent();
    expect(component.text()).not.toContain('Geen resultaten gevonden');

    await (component as any).setProps({ results: [] });
    expect(component.text()).toContain('Geen resultaten gevonden');
  });
});
