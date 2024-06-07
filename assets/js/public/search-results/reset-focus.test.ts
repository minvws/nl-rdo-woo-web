import { MockInstance, afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { resetFocus } from './reset-focus';

describe('The "resetFocus" function', () => {
  describe('the "initialize" function', () => {
    const { initialize } = resetFocus();

    interface CreateElementOptions {
      withAriaControls: boolean;
      withId: boolean;
      withNameAndValue: boolean;
    }
    const createPreviousActiveElement = (options: Partial<CreateElementOptions> = {}) => {
      const { withAriaControls = false, withId = false, withNameAndValue = false } = options;
      const element = document.createElement('input');
      if (withAriaControls) {
        element.setAttribute('aria-controls', 'test');
      }
      if (withId) {
        element.setAttribute('id', 'test');
      }
      if (withNameAndValue) {
        element.setAttribute('name', 'test');
        element.setAttribute('value', 'test');
      }

      return element;
    };

    describe('resetting the focus based on the previous active element', () => {
      let inputElementFocusSpy: MockInstance;
      let numberOfResultsElementFocusSpy: MockInstance;

      beforeEach(() => {
        document.body.innerHTML = `
          <h1 id="js-number-of-search-results" tabindex="-1">Test</h1>
          <input id="test" aria-controls="test" name="test" value="test" />
        `;

        const inputElement = document.querySelector('input') as HTMLInputElement;
        const numberOfResultsElement = document.querySelector('h1') as HTMLHeadingElement;
        inputElementFocusSpy = vi.spyOn(inputElement, 'focus');
        numberOfResultsElementFocusSpy = vi.spyOn(numberOfResultsElement, 'focus');
      });

      afterEach(() => {
        vi.resetAllMocks();
      });

      test('should reset focus to an element with the same id as the previous active element', () => {
        initialize(createPreviousActiveElement({ withId: true }));

        expect(inputElementFocusSpy).toHaveBeenCalledTimes(1);
      });

      test('should reset focus to an element with the same "aria-controls" attribute as the previous active element', () => {
        initialize(createPreviousActiveElement({ withAriaControls: true }));

        expect(inputElementFocusSpy).toHaveBeenCalledTimes(1);
      });

      test('should reset focus to an element with the same "name" and "value" attributes as the previous active element', () => {
        initialize(createPreviousActiveElement({ withNameAndValue: true }));

        expect(inputElementFocusSpy).toHaveBeenCalledTimes(1);
      });

      test('should reset focus to #js-number-of-search-results if the previous active element is provided but not found', () => {
        expect(numberOfResultsElementFocusSpy).toHaveBeenCalledTimes(0);

        initialize(createPreviousActiveElement());

        expect(inputElementFocusSpy).toHaveBeenCalledTimes(0);
        expect(numberOfResultsElementFocusSpy).toHaveBeenCalledTimes(1);
      });

      test('should not reset focus when no previous active element is provided', () => {
        initialize();

        expect(inputElementFocusSpy).toHaveBeenCalledTimes(0);
        expect(numberOfResultsElementFocusSpy).toHaveBeenCalledTimes(0);
      });
    });
  });
});
