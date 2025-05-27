import { flushPromises, mount, VueWrapper } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import PublicationSearchInput from './PublicationSearchInput.vue';

vi.mock('@js/admin/utils');
vi.mock('@utils');

describe('The "PublicationSearchInput" component', () => {
  interface Options {
    dossierId: string;
    publicationType: string;
    resultType: string;
  }

  const createComponent = (options: Partial<Options> = {}) =>
    mount(PublicationSearchInput, {
      props: {
        ariaAutocomplete: 'list',
        ariaHaspopup: 'dialog',
        dossierId: options.dossierId,
        id: 'mocked-id',
        isExpanded: false,
        placeholder: 'Mocked placeholder',
        publicationType: options.publicationType,
        resultType: options.resultType,
      },
      shallow: true,
    });

  const getInputElement = (component: VueWrapper) => component.find('input');
  const getMagnifierIconElement = (component: VueWrapper) =>
    component.find('span icon-stub');
  const getResetIconElement = (component: VueWrapper) =>
    component.find('button icon-stub');
  const setInputValue = async (component: VueWrapper, value: string) => {
    await getInputElement(component).setValue(value);
    await vi.advanceTimersByTimeAsync(value.length * 251);
    await flushPromises();
  };
  const setInvalidInputValue = (component: VueWrapper) =>
    setInputValue(component, 'ab');
  const setValidInputValue = (component: VueWrapper) =>
    setInputValue(component, 'abcd');

  const mockedRetrievedResults = [
    {
      id: 'mocked-id-abc',
      link: 'mocked-link-abc',
      number: null,
      title: 'mocked-title-abc',
      type: 'attachment',
    },
    {
      id: 'mocked-id-def',
      link: 'mocked-link-def',
      number: null,
      title: 'mocked-title-def',
      type: 'document',
    },
  ];

  beforeEach(() => {
    window.fetch = vi.fn().mockImplementation(() =>
      Promise.resolve({
        json: () => Promise.resolve(mockedRetrievedResults),
      }),
    );

    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.resetAllMocks();
  });

  test('should render an input with the correct attributes', () => {
    const component = createComponent();
    const inputElement = getInputElement(component);

    expect(inputElement.attributes('aria-autocomplete')).toBe('list');
    expect(inputElement.attributes('aria-controls')).toBe(
      'search-publications-results',
    );
    expect(inputElement.attributes('aria-expanded')).toBe('false');
    expect(inputElement.attributes('aria-haspopup')).toBe('dialog');
    expect(inputElement.attributes('autocomplete')).toBe('off');
    expect(inputElement.attributes('id')).toBe('mocked-id');
    expect(inputElement.attributes('name')).toBe('query');
    expect(inputElement.attributes('placeholder')).toBe('Mocked placeholder');
    expect(inputElement.attributes('role')).toBe('combobox');
    expect(inputElement.attributes('type')).toBe('text');
  });

  test('should display a magnifier icon by default', () => {
    const component = createComponent();

    expect(getMagnifierIconElement(component).attributes('name')).toBe(
      'magnifier',
    );
    expect(getResetIconElement(component).exists()).toBeFalsy();
  });

  test('should display a reset icon (and hide the maginifier icon) when the query contains at least 3 characters', async () => {
    const component = createComponent();

    expect(getMagnifierIconElement(component).exists()).toBeTruthy();
    expect(getResetIconElement(component).exists()).toBeFalsy();

    await setInputValue(component, 'test');

    expect(getMagnifierIconElement(component).exists()).toBeFalsy();
    expect(getResetIconElement(component).exists()).toBeTruthy();
  });

  describe('when the user provides at least 3 characters', () => {
    test('should retrieve search results', async () => {
      const component = createComponent();

      await setInvalidInputValue(component);
      expect(window.fetch).not.toHaveBeenCalled();

      await setInputValue(component, 'abcdefgh');
      expect(window.fetch).toHaveBeenNthCalledWith(
        1,
        'https://mocked-origin.com/balie/api/publication/search?q=abcdefgh',
      );
    });

    test('should emit "resultsUpdated" when search results are retrieved', async () => {
      const component = createComponent();

      expect(component.emitted().resultsUpdated).toBeUndefined();

      await setValidInputValue(component);
      expect(component.emitted().resultsUpdated[0]).toEqual([
        mockedRetrievedResults,
      ]);
    });

    test('should emit "showResults" when search results are retrieved', async () => {
      const component = createComponent();

      await setInvalidInputValue(component);
      expect(component.emitted('showResults')).toBeUndefined();

      await setValidInputValue(component);
      expect(component.emitted('showResults')).toBeTruthy();
    });
  });

  describe('the endpoint', () => {
    test('should be created with the correct parameters', async () => {
      const component = createComponent({ dossierId: 'mocked-dossier-id' });

      await setInputValue(component, 'abc');
      expect(window.fetch).toHaveBeenCalledWith(
        'https://mocked-origin.com/balie/api/publication/search?q=abc&dossierId=mocked-dossier-id',
      );

      await component.setProps({
        publicationType: 'mocked-publication-type',
        resultType: 'mocked-result-type',
      });
      await setInputValue(component, 'abcd');

      expect(window.fetch).toHaveBeenCalledWith(
        encodeURI(
          'https://mocked-origin.com/balie/api/publication/search?q=abcd&dossierId=mocked-dossier-id&filter[publicationType]=mocked-publication-type&filter[resultType]=mocked-result-type',
        ),
      );
    });
  });

  describe('when pressing the reset query icon', () => {
    test('should reset the input field', async () => {
      const component = createComponent();

      await setValidInputValue(component);
      expect(getInputElement(component).element.value).toBe('abcd');

      await getResetIconElement(component).trigger('click');
      expect(getInputElement(component).element.value).toBe('');
    });
  });

  describe('when the user provides too little characters', () => {
    test('should emit "resultsUpdated" with an empty list of results', async () => {
      const component = createComponent();

      await setValidInputValue(component);
      expect(component.emitted().resultsUpdated[0]).toEqual([
        mockedRetrievedResults,
      ]);

      await setInvalidInputValue(component);
      expect(component.emitted().resultsUpdated[1]).toEqual([[]]);
    });

    test('should emit "hideResults"', async () => {
      const component = createComponent();

      expect(component.emitted('hideResults')).toBeUndefined();

      await setInvalidInputValue(component);
      expect(component.emitted('hideResults')).toBeTruthy();

      await getInputElement(component).trigger('focus');
    });
  });

  test('should emit "showResults" when the input field receives focus', async () => {
    const component = createComponent();

    await setValidInputValue(component);
    expect(component.emitted('showResults')).toHaveLength(1);

    await getInputElement(component).trigger('focus');
    expect(component.emitted('showResults')).toHaveLength(2);
  });
});
