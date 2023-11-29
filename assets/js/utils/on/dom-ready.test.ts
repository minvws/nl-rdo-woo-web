import { describe, expect, test } from '@jest/globals';
import { getDocument } from '../browser';
import type { DocumentMock } from '../mocks';
import { onDomReady } from './dom-ready';

jest.mock('../browser');

describe('the "onDomReady" function', () => {
  let someFunction: jest.Mock;

  beforeEach(() => {
    someFunction = jest.fn();
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
