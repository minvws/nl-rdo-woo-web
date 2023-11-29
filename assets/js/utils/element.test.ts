import { describe, expect, test } from '@jest/globals';
import { hideElement, showElement } from './element';

describe('The element utility functions', () => {
  describe('the "hideElement" function', () => {
    test('hides the provided element by adding the "hidden" class name to the element', () => {
      const element = document.createElement('div');

      hideElement(element);
      expect(element.classList.contains('hidden')).toBe(true);
    });
  });

  describe('the "showElement" function', () => {
    test('shows the provided element by removing the "hidden" class name from the element', () => {
      const element = document.createElement('div');
      element.classList.add('hidden');

      showElement(element);
      expect(element.classList.contains('hidden')).toBe(false);
    });
  });
});
