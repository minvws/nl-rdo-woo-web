import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import PublicationSearchAndGo from './PublicationSearchAndGo.vue';

describe('The "PublicationSearchAndGo" component', () => {
  const createComponent = () => {
    return mount(PublicationSearchAndGo, {
      props: {
        dossierId: 'mocked_dossier_id',
        label: 'Mocked label',
        resultType: 'mocked_result_type',
      },
    });
  };

  const getInputComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'PublicationSearchInput' });

  const getSearchResultsComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'SearchResults' });

  const getSearchResultsWrapper = (component: VueWrapper) =>
    component.find('.bhr-overlay-card');

  const showSearchResults = async (component: VueWrapper) => {
    const inputComponent = getInputComponent(component);
    await inputComponent.vm.$emit('showResults');
  };

  const onFormEmit = async (component: VueWrapper, event: string) => {
    const formComponent = component.findComponent({
      name: 'PublicationSearchForm',
    });
    await formComponent.vm.$emit(event);
  };

  const searchResultsAreHidden = (component: VueWrapper) => {
    const searchResultsComponent = getSearchResultsWrapper(component);
    return searchResultsComponent.classes().includes('hidden');
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
    expect(getInputComponent(createComponent()).props()).toMatchObject({
      ariaHaspopup: 'dialog',
      dossierId: 'mocked_dossier_id',
      resultType: 'mocked_result_type',
    });
  });

  test('should display the search results', async () => {
    const component = createComponent();
    const searchResultsComponent = getSearchResultsComponent(component);

    const mockedSearchResults = [
      { id: 1, title: 'Mocked result 1' },
      { id: 2, title: 'Mocked result 2' },
    ];

    await retrieveSearchResults(component, mockedSearchResults);
    expect(searchResultsComponent.props('results')).toEqual(
      mockedSearchResults,
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
});
