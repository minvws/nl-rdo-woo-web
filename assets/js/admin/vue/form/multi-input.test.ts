import { describe, expect, test } from 'vitest';
import { createName, getOtherValues, shouldAutoFocus } from './multi-input';

describe('The functionality used in multi-input components', () => {
  describe('the "createName" function', () => {
    test('adds an index to a name without an existing index', () => {
      expect(createName('colors', 0)).toBe('colors[0]');
      expect(createName('items', 1)).toBe('items[1]');
    });

    test('replaces an existing index with a new index', () => {
      expect(createName('colors[0]', 1)).toBe('colors[1]');
      expect(createName('items[5]', 2)).toBe('items[2]');
    });
  });

  describe('the "getOtherValues" function', () => {
    const items = [
      { id: '1', value: 'red' },
      { id: '2', value: 'blue' },
      { id: '3', value: 'green' },
    ];

    test('returns values of other items', () => {
      expect(getOtherValues('1', items)).toEqual(['blue', 'green']);
      expect(getOtherValues('2', items)).toEqual(['red', 'green']);
    });
  });

  describe('the "shouldAutoFocus" function', () => {
    const items = [
      { id: '1', value: 'rood' },
      { id: '2', value: '' },
      { id: '3', value: '' },
    ];

    test('returns false if the number of items is equal to minLength', () => {
      expect(shouldAutoFocus(2, items, 3)).toBe(false);
    });

    test('returns false if the item has a value', () => {
      expect(shouldAutoFocus(0, items)).toBe(false);
    });

    test('returns true for the last empty item', () => {
      expect(shouldAutoFocus(2, items)).toBe(true);
    });

    test('returns false for the non-last empty item', () => {
      expect(shouldAutoFocus(1, items)).toBe(false);
    });
  });
});
