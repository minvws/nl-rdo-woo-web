import SearchPreviewsForm from '@admin-fe/component/publication/search/SearchPreviewsForm.vue';
import { flushPromises, mount, VueWrapper } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { nextTick } from 'vue';

let onEscapeCallback: any;
let onFocusOutCallback: any;

vi.mock('@utils', async (importOriginal) => {
  const original = await importOriginal<typeof import('@utils')>();
  return {
    ...original,
    onFocusOut: vi.fn().mockImplementation((_, callback) => {
      onFocusOutCallback = callback;
    }),
    onKeyDown: vi.fn().mockImplementation((key, callback) => {
      if (key === 'Escape') {
        onEscapeCallback = callback;
      }
    }),
  };
});

describe('The "SearchPreviewsForm" component', () => {
  const createComponent = (id?: string) =>
    mount(SearchPreviewsForm, {
      props: {
        endpoint: 'https://mocked-endpoint.com',
        id,
        label: 'Mocked label',
      },
      shallow: true,
    });

  const getInputElement = (component: VueWrapper) => component.find('input');
  const getLabelElement = (component: VueWrapper) => component.find('label');
  const getResetButtonElement = (component: VueWrapper) =>
    component.find('button');
  const getMagnifierIcon = (component: VueWrapper) => {
    const iconComponents = component.findAllComponents({ name: 'Icon' });
    return iconComponents.find(
      (iconComponent) => iconComponent.props('name') === 'magnifier',
    );
  };
  const updateInputValue = async (component: VueWrapper, value: string) => {
    await getInputElement(component).setValue(value);
    vi.advanceTimersByTime(251); // Make the debounce function execute
    await flushPromises();
  };
  const getSearchResultsComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'SearchResults' });
  const areSearchResultsVisible = (component: VueWrapper) =>
    !component
      .find('#search-previews-results')
      .element.classList.contains('hidden');

  const mockedSearchResults = [{ title: 'result-1' }, { title: 'result-2' }];

  const mockSearchResults = (results: any[]) => {
    global.fetch = vi
      .fn()
      .mockImplementation(() =>
        Promise.resolve({ json: () => Promise.resolve(results) }),
      );
  };

  beforeEach(() => {
    vi.useFakeTimers();
    mockSearchResults(mockedSearchResults);
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  test('should display an input field', () => {
    const component = createComponent();
    expect(getInputElement(component).exists()).toBe(true);
  });

  describe('the label', () => {
    test('should be tied to the input field with the "for" and "id" attributes', () => {
      const component = createComponent();
      expect(getInputElement(component).element.getAttribute('id')).toBe(
        getLabelElement(component).element.getAttribute('for'),
      );
    });

    test('should have a text which equals the provided label text', () => {
      const component = createComponent();
      expect(getLabelElement(component).text()).toBe('Mocked label');
    });
  });

  describe('the maginifier glass', () => {
    test('should be displayed by default', () => {
      const component = createComponent();
      expect(getMagnifierIcon(component)).toBeTruthy();
    });

    test('should become hidden when the search field does contain any characters', async () => {
      const component = createComponent();
      expect(getMagnifierIcon(component)).toBeTruthy();

      await updateInputValue(component, 'mocked search query');
      expect(getMagnifierIcon(component)).toBeFalsy();
    });
  });

  describe('the button to empty the search field', () => {
    test('should be hidden by default', () => {
      const component = createComponent();
      expect(getResetButtonElement(component).exists()).toBe(false);
    });

    test('should become visible when the search field does contain any characters', async () => {
      const component = createComponent();
      expect(getResetButtonElement(component).exists()).toBe(false);

      await updateInputValue(component, 'mocked search query');
      expect(getResetButtonElement(component).exists()).toBe(true);
    });

    test('should empty the search field when clicking it', async () => {
      const component = createComponent();

      await updateInputValue(component, 'mocked search query');
      expect(getInputElement(component).element.value).toBe(
        'mocked search query',
      );

      await getResetButtonElement(component).trigger('click');
      expect(getInputElement(component).element.value).toBe('');
    });
  });

  describe('when entering a search query', () => {
    test('it should not make a request to fetch search results when the query contains only 2 characters or less', async () => {
      const component = createComponent();

      await updateInputValue(component, 'ab');
      expect(global.fetch).not.toHaveBeenCalled();
    });

    test('it should make a request to fetch search results when the query contains 3 characters or more', async () => {
      const component = createComponent();

      await updateInputValue(component, 'abc');
      expect(global.fetch).toHaveBeenNthCalledWith(
        1,
        'https://mocked-endpoint.com?q=abc',
      );
    });

    test('it should display the fetched search results', async () => {
      const component = createComponent();

      expect(getSearchResultsComponent(component).props('results')).toEqual([]);
      expect(areSearchResultsVisible(component)).toBe(false);

      await updateInputValue(component, 'abc');
      expect(areSearchResultsVisible(component)).toBe(true);
      expect(getSearchResultsComponent(component).props('results')).toEqual(
        mockedSearchResults,
      );
    });
  });

  describe('the search results', () => {
    test('should become hidden when the user presses the Escape key', async () => {
      const component = createComponent();
      await updateInputValue(component, 'abc');
      expect(areSearchResultsVisible(component)).toBe(true);

      onEscapeCallback();
      await nextTick();

      expect(areSearchResultsVisible(component)).toBe(false);
    });

    test('should become hidden when the user sets the focus on an element outside the search from', async () => {
      const component = createComponent();
      await updateInputValue(component, 'abc');
      expect(areSearchResultsVisible(component)).toBe(true);

      onFocusOutCallback();
      await nextTick();

      expect(areSearchResultsVisible(component)).toBe(false);
    });

    test('should become visible again when the user sets the focus on the input field', async () => {
      const component = createComponent();
      await updateInputValue(component, 'abc');
      expect(areSearchResultsVisible(component)).toBe(true);

      onFocusOutCallback();
      await nextTick();

      expect(areSearchResultsVisible(component)).toBe(false);

      await getInputElement(component).trigger('focus');
      expect(areSearchResultsVisible(component)).toBe(true);
    });
  });
});
