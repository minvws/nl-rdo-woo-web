import { describe, expect, test } from 'vitest';
import { moveItem } from './content-tree';

describe('The "moveItem" function', () => {
  test('should swap an item with the next one when moving down', () => {
    expect(moveItem(['a', 'b', 'c'], 0, 'down')).toEqual(['b', 'a', 'c']);
  });

  test('should swap an item with the previous one when moving up', () => {
    expect(moveItem(['a', 'b', 'c'], 1, 'up')).toEqual(['b', 'a', 'c']);
  });

  test('should wrap the last item to the top when moving down', () => {
    expect(moveItem(['a', 'b', 'c'], 2, 'down')).toEqual(['c', 'b', 'a']);
  });

  test('should wrap the first item to the bottom when moving up', () => {
    expect(moveItem(['a', 'b', 'c'], 0, 'up')).toEqual(['c', 'b', 'a']);
  });

  test('should not mutate the original array', () => {
    const items = ['a', 'b', 'c'];

    moveItem(items, 0, 'down');

    expect(items).toEqual(['a', 'b', 'c']);
  });
});
