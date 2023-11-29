import { describe, expect, test } from '@jest/globals';
import { getWindow } from '../browser';
import { onBeforeUnload } from './before-unload';

jest.mock('../browser');

describe('the "onBeforeUnload" function', () => {
  test('should execute the provided function when unloading the page', () => {
    const someFunction = () => {};
    expect(getWindow().addEventListener).not.toHaveBeenCalled();

    onBeforeUnload(someFunction);
    expect(getWindow().addEventListener).toHaveBeenCalledTimes(1);
    expect(getWindow().addEventListener).toHaveBeenCalledWith('beforeunload', someFunction, { once: true });
  });
});
