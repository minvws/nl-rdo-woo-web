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

    void debouncedFunction();
    void debouncedFunction();
    const lastFirstCall = debouncedFunction();

    vi.advanceTimersByTime(101);
    await lastFirstCall;

    expect(mockedFunction).toHaveBeenCalledTimes(1);

    void debouncedFunction();
    const lastSecondCall = debouncedFunction();
    vi.advanceTimersByTime(101);
    await lastSecondCall;

    expect(mockedFunction).toHaveBeenCalledTimes(2);
  });
});
