import { describe, expect, test, vi } from 'vitest';
import { onOneOfKeysDown } from './one-of-keys-down';

describe('the "onOneOfKeysDown" function', () => {
  const pressKey = (key: string) => {
    window.dispatchEvent(new KeyboardEvent('keydown', { key }));
  };

  test('should only invoke the provided function when the provided key is pressed', () => {
    const someFunction = vi.fn();

    onOneOfKeysDown(['Escape', 'ArrowLeft'], someFunction);
    expect(someFunction).not.toHaveBeenCalled();

    pressKey('Tab');
    expect(someFunction).not.toHaveBeenCalled();

    pressKey('Escape');
    expect(someFunction).toHaveBeenCalledTimes(1);

    pressKey('ArrowLeft');
    expect(someFunction).toHaveBeenCalledTimes(2);
  });
});
