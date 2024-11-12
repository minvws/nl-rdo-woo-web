import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import PublicationSearchAutocomplete from './PublicationSearchAutocomplete.vue';
import { nextTick } from 'vue';

describe('The "PublicationSearchAutocomplete" component', () => {
  const createComponent = () => {
    return mount(PublicationSearchAutocomplete, {
      props: {
        endpoint: 'https://mocked-endpoint.mock',
        label: 'Mocked label',
      },
    });
  };

  const getMockedSearchResults = () => [
    { id: 1, title: 'Mocked result 1' },
    { id: 2, title: 'Mocked result 2' },
  ];

  const getInputComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'PublicationSearchInput' });

  const getSearchResultsComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'SearchResultsListbox' });

  const getSearchResultsWrapper = (component: VueWrapper) =>
    component.find('[role="listbox"]');

  const showSearchResults = async (component: VueWrapper) => {
    const inputComponent = getInputComponent(component);
    await inputComponent.vm.$emit('showResults');
  };

  const searchResultsAreHidden = (component: VueWrapper) => {
    const searchResultsComponent = getSearchResultsWrapper(component);
    return searchResultsComponent.classes().includes('hidden');
  };

  const selectSearchResult = (component: VueWrapper, result: object) => {
    const searchResultsComponent = getSearchResultsComponent(component);
    return searchResultsComponent.vm.$emit('select', result);
  };

  const onFormEmit = async (component: VueWrapper, event: string) => {
    const formComponent = component.findComponent({
      name: 'PublicationSearchForm',
    });
    await formComponent.vm.$emit(event);
  };

  const retrieveSearchResults = async (
    component: VueWrapper,
    results: object[],
  ) => {
    const inputComponent = getInputComponent(component);
    await inputComponent.vm.$emit('resultsUpdated', results);
  };

  test('should display the provided label', () => {
    const component = createComponent();
    expect(component.find('label').text()).toBe('Mocked label');
  });

  test('should display an input field which retrieves search results', () => {
    const component = createComponent();
    const inputComponent = getInputComponent(component);

    expect(inputComponent.props('ariaHaspopup')).toBe('listbox');
    expect(inputComponent.props('endpoint')).toBe(
      'https://mocked-endpoint.mock',
    );
    expect(inputComponent.props('placeholder')).toBe('Zoeken op dossiernummer');
  });

  test('should display the search results', async () => {
    const component = createComponent();

    await Promise.all([
      retrieveSearchResults(component, getMockedSearchResults()),
      showSearchResults(component),
    ]);

    expect(getSearchResultsComponent(component).props('results')).toEqual(
      getMockedSearchResults(),
    );
  });

  test('should hide the search results when the user presses escape', async () => {
    const component = createComponent();

    await showSearchResults(component);
    expect(searchResultsAreHidden(component)).toBe(false);

    await onFormEmit(component, 'escape');
    expect(searchResultsAreHidden(component)).toBe(true);
  });

  test('should hide the search results when the search form loses focus', async () => {
    const component = createComponent();

    await showSearchResults(component);
    expect(searchResultsAreHidden(component)).toBe(false);

    await onFormEmit(component, 'focusOut');
    expect(searchResultsAreHidden(component)).toBe(true);
  });

  test('should emit "select" when a search result is selected', async () => {
    const component = createComponent();

    expect(component.emitted('select')).toBeUndefined();

    await showSearchResults(component);
    const mockedSelectedResult = { id: 1, title: 'Mocked result 1' };
    await selectSearchResult(component, mockedSelectedResult);

    expect(component.emitted('select')).toBeTruthy();
    expect(searchResultsAreHidden(component)).toBe(true);
  });

  test('should hide and empty the search results when the exposed function "reset" is invoked', async () => {
    const component = createComponent();

    await Promise.all([
      retrieveSearchResults(component, getMockedSearchResults()),
      showSearchResults(component),
    ]);

    expect(searchResultsAreHidden(component)).toBe(false);

    component.vm.reset();
    await nextTick();

    expect(searchResultsAreHidden(component)).toBe(true);
    expect(getSearchResultsComponent(component).exists()).toBe(false);
  });
});
