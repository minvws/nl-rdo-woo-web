import { describe, expect, test } from 'vitest';
import { hideElement, isElementHidden, isElementVisible, showElement } from './element';

describe('The element utility functions', () => {
  const createElement = () => document.createElement('div');

  describe('the "hideElement" function', () => {
    test('hides the provided element by adding the "hidden" class name to the element', () => {
      const element = createElement();

      hideElement(element);
      expect(element.classList.contains('hidden')).toBe(true);
    });
  });

  describe('the "isElementHidden" function', () => {
    test('should return true when the provided element contains the class name "hidden"', () => {
      const element = createElement();
      expect(isElementHidden(element)).toBe(false);

      hideElement(element);
      expect(isElementHidden(element)).toBe(true);
    });
  });

  describe('the "isElementVisible" function', () => {
    test('should return true when the provided element does not contain the class name "hidden"', () => {
      const element = createElement();
      expect(isElementVisible(element)).toBe(true);

      hideElement(element);
      expect(isElementVisible(element)).toBe(false);
    });
  });

  describe('the "showElement" function', () => {
    test('shows the provided element by removing the "hidden" class name from the element', () => {
      const element = createElement();
      element.classList.add('hidden');

      showElement(element);
      expect(element.classList.contains('hidden')).toBe(false);
    });
  });
});
