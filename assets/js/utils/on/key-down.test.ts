import { describe, expect, test, vi } from 'vitest';
import { onKeyDown } from './key-down';

describe('the "onKeyDown" function', () => {
  const pressKey = (key: string) => {
    window.dispatchEvent(new KeyboardEvent('keydown', { key }));
  };

  test('should only invoke the provided function when the provided key is pressed', () => {
    const someFunction = vi.fn();

    onKeyDown('Escape', someFunction);
    expect(someFunction).not.toHaveBeenCalled();

    pressKey('Tab');
    expect(someFunction).not.toHaveBeenCalled();

    pressKey('Escape');
    expect(someFunction).toHaveBeenCalledTimes(1);
  });
});
