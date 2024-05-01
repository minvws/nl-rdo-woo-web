import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { debounce } from './debounce';

describe('The "debounce" function', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  test('should invoke the provided function only once within a time frame having a duration of the provided value', async () => {
    const mockedFunction = vi.fn();
    const debouncedFunction = debounce(mockedFunction, 100);

    debouncedFunction();
    debouncedFunction();
    debouncedFunction();

    vi.advanceTimersByTime(101);

    expect(mockedFunction).toHaveBeenCalledTimes(1);

    debouncedFunction();
    debouncedFunction();
    vi.advanceTimersByTime(101);

    expect(mockedFunction).toHaveBeenCalledTimes(2);
  });
});
