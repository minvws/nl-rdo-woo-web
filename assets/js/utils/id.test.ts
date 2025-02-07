import { describe, expect, test, vi } from 'vitest';
import { uniqueId } from './id';

vi.mock('./browser');

describe('The "uniqueId" function', () => {
  test('should return a unique id using the "randomUUID" function of the crypto implementation', () => {
    const expectedValue = 'some-mocked-random-uuid-some-mocked-random-uuid';
    expect(uniqueId('', expectedValue.length)).toBe(expectedValue);
  });

  test('should return a unique id of 8 characters by default', () => {
    expect(uniqueId()).toBe('some-moc');
  });

  test('should return a unique id with the provided prefix', () => {
    const prefix = 'some-prefix';
    const mockedRandomUuid = 'some-mocked-random-uuid';
    const { length } = mockedRandomUuid;
    expect(uniqueId(prefix, length)).toBe(`${prefix}-${mockedRandomUuid}`);
  });
});
