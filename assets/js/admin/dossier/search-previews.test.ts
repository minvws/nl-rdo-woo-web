import { Mock, MockInstance, afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { searchPreviews, type AddExternalFunctionalityFunction } from './search-previews';

describe('The search previews functionality', () => {
  let addFunctionalityFunctionMock: Mock;
  let removeFunctionalityFunctionMock: Mock;
  let formAddEventListenerSpy: MockInstance;
  let submitFormEventMock: { preventDefault: Mock };

  let cleanup: () => void;
  let initialize: (
    formElementId: string,
    addFunctionalityFunction: AddExternalFunctionalityFunction,
    removeFunctionalityFunction: () => void
  ) => void;

  const getCrossButtonElement = () => document.querySelector('.js-icon-cross') as HTMLButtonElement;
  const getFormElement = () => document.querySelector('form') as HTMLFormElement;
  const getInputElement = () => document.querySelector('.js-input') as HTMLInputElement;
  const getInputValue = () => getInputElement().value;
  const getSearchResultsContainerContent = () => document.querySelector('.js-placeholder')?.textContent;
  const updateInputValue = (value: string) => {
    const inputElement = getInputElement();
    inputElement.value = value;
    inputElement.dispatchEvent(new Event('input'));
    vi.advanceTimersByTime(251); // Make the debounce function execute
  };

  beforeEach(() => {
    global.fetch = vi.fn(() => Promise.resolve({
      json: () => Promise.resolve({
        results: JSON.stringify('mocked-search-result-1'),
      }),
    })) as Mock;

    vi.useFakeTimers();

    addFunctionalityFunctionMock = vi.fn();
    removeFunctionalityFunctionMock = vi.fn();

    document.body.innerHTML = `
      <form id="js-mocked-form" data-endpoint="mocked-endpoint" onsubmit="return false;">
        <div class="js-overlay"></div>
        <input class="js-input" />
        <button class="js-icon-cross hidden" type="buton">Cross button</button>
        <span class="js-icon-magnifier"></span>
        <div class="js-placeholder"></div>
        <button type="submit">Submit</button>
      </form>
    `;

    submitFormEventMock = {
      preventDefault: vi.fn(),
    };
    formAddEventListenerSpy = vi.spyOn(getFormElement(), 'addEventListener')
      .mockImplementation((type: string, callback: EventListenerOrEventListenerObject): void => {
        if (type === 'submit') {
          (callback as EventListener)(submitFormEventMock as any);
        }
      });

    ({ cleanup, initialize } = searchPreviews());
    initialize('js-mocked-form', addFunctionalityFunctionMock, removeFunctionalityFunctionMock);
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
    vi.useRealTimers();
  });

  describe('the cross button', () => {
    test('should not become visible by default', () => {
      expect(getCrossButtonElement().classList.contains('hidden')).toBe(true);
    });

    test('should become visible when the user filled in at least one character in the sarch field', () => {
      updateInputValue('this is my query');
      expect(getCrossButtonElement().classList.contains('hidden')).toBe(false);
    });

    test('should empty the search field when clicked', () => {
      updateInputValue('this is my query');
      expect(getInputValue()).toBe('this is my query');

      getCrossButtonElement().click();
      expect(getInputValue()).toBe('');
    });

    test('should have its event listener removed when the search previews functionality is cleaned up', () => {
      updateInputValue('this is my query');
      expect(getInputValue()).toBe('this is my query');

      cleanup();

      getCrossButtonElement().click();
      expect(getInputValue()).toBe('this is my query'); // Input vlue should not have changed
    });
  });

  describe('search results', () => {
    test('should be fetched when the user starts searching and fills in at least 3 characters', () => {
      expect(global.fetch).not.toHaveBeenCalled();

      updateInputValue('12');

      expect(global.fetch).not.toHaveBeenCalled();

      updateInputValue('this is my search query');

      expect(global.fetch).toHaveBeenCalledWith('mocked-endpoint', {
        method: 'POST',
        body: JSON.stringify({ q: 'this is my search query' }),
        headers: {
          'Content-Type': 'application/json',
        },
      });
    });

    test('should be displayed when the request responds with one or more search results', async () => {
      expect(getSearchResultsContainerContent()).toBe('');

      updateInputValue('search query');

      await vi.waitFor(
        () => {
          expect(getSearchResultsContainerContent()).toContain('mocked-search-result-1');
        },
      );
    });
  });

  test('should prevent the form from being submitted', () => {
    expect(formAddEventListenerSpy).toHaveBeenCalledWith('submit', expect.any(Function), { signal: expect.any(AbortSignal) });
    expect(submitFormEventMock.preventDefault).toHaveBeenCalled();
  });
});
