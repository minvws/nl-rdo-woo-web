import { describe, expect, test } from 'vitest';
import { capitalize, pluralize, removeAccents } from './string';

describe('The string utility functions', () => {
  describe('the "capitalize" function', () => {
    test('should return the provided string with the first character in capitals', () => {
      expect(capitalize('this')).toBe('This');
    });
  });

  describe('the "pluralize" function', () => {
    test('should return the plural prodived version in case the provided number does not equal one', () => {
      expect(pluralize('element', 'elements', 0)).toBe('elements');
      expect(pluralize('element', 'elements', 2)).toBe('elements');
    });

    test('should return the singular prodived version in case the provided number does not equal one', () => {
      expect(pluralize('element', 'elements', 1)).toBe('element');
    });
  });

  describe('the "removeAccents" function', () => {
    test('should return the provided string with accents removed', () => {
      expect(removeAccents('åçé')).toBe('ace');
    });
  });
});
