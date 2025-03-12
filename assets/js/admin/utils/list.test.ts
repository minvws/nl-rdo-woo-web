import { describe, expect, test } from 'vitest';
import { formatList } from './list';

describe('The list utility functions', () => {
  describe('the "formatList" function', () => {
    test('should return an empty string if no list is provided', () => {
      expect(formatList(undefined as any, '')).toBe('');
    });

    test('should return an empty string if the provided list is empty', () => {
      expect(formatList([], 'final glue')).toBe('');
    });

    test('should return the only item if the list contains one item', () => {
      expect(formatList(['only'], 'final glue')).toBe('only');
    });

    test('should return the first and second item with the provided final glue if the provided list contains 2 items', () => {
      expect(formatList(['first', 'second'], 'final glue')).toBe(
        'first final glue second',
      );
    });

    test('should return all items separated with the provided regular glue and the final glue as the final glue', () => {
      expect(formatList(['first', 'second', 'third'], 'and')).toBe(
        'first, second and third',
      );
    });

    test('should not adjust the provided list', () => {
      const list = ['first', 'second', 'third'];
      formatList(list, 'and');
      expect(list).toEqual(['first', 'second', 'third']);
    });
  });
});
