import { Mock, afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { getDocument } from '../browser';
import type { DocumentMock } from '../mocks';
import { onDomReady } from './dom-ready';

vi.mock('../browser');

describe('the "onDomReady" function', () => {
  let someFunction: Mock;

  beforeEach(() => {
    someFunction = vi.fn();
  });

  afterEach(() => {
    (getDocument() as unknown as DocumentMock).readyState = 'loading';
  });

  test('should, if the dom is ready, invoke the provided function immediately', () => {
    (getDocument() as unknown as DocumentMock).readyState = 'complete';

    onDomReady(someFunction);
    expect(someFunction).toHaveBeenCalledTimes(1);
  });

  test('should invoke the provided function as soon as the dom is ready', () => {
    onDomReady(someFunction);

    expect(someFunction).not.toHaveBeenCalled();
    expect(getDocument().addEventListener).toHaveBeenCalledTimes(1);
    expect(getDocument().addEventListener).toHaveBeenCalledWith('DOMContentLoaded', someFunction);
  });
});
