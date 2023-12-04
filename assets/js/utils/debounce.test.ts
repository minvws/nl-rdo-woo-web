import { describe, expect, test } from '@jest/globals';

jest.useFakeTimers();

describe('The "debounce" function', () => {
  test('should invoke the provided function only once within a time frame having a duration of the provided value', async () => {
    const { debounce } = require('./debounce');

    const mockedFunction = jest.fn();
    const debouncedFunction = debounce(mockedFunction, 100);

    debouncedFunction();
    debouncedFunction();
    debouncedFunction();

    jest.advanceTimersByTime(101);

    expect(mockedFunction).toHaveBeenCalledTimes(1);
  });
});
