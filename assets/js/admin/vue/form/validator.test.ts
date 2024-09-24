import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { dateMaxUntilToday, email, forbidden, minLength, required } from './validator';

describe('the "required" validator', () => {
  test('should return a required error if the provided value is empty-like', () => {
    const expectedError = { id: 'required' };

    expect(required()(undefined as any)).toEqual(expectedError);
    expect(required()(null as any)).toEqual(expectedError);
    expect(required()('')).toEqual(expectedError);
    expect(required()(' ')).toEqual(expectedError);
    expect(required()({})).toEqual(expectedError);
    expect(required()([])).toEqual(expectedError);
  });

  test('should return undefined if the provided value is not empty-like', () => {
    expect(required()(0)).toBeUndefined();
    expect(required()(1)).toBeUndefined();
    expect(required()('1')).toBeUndefined();
    expect(required()([1])).toBeUndefined();
    expect(required()({ 1: '1' })).toBeUndefined();
  });
});

describe('the "email" validator', () => {
  test('should return an email error if the provided value is not an email adress', () => {
    const expectedError = { id: 'email' };

    expect(email()(undefined as any)).toEqual(expectedError);
    expect(email()(null as any)).toEqual(expectedError);
    expect(email()([])).toEqual(expectedError);
    expect(email()({})).toEqual(expectedError);
    expect(email()('{}')).toEqual(expectedError);
    expect(email()('1')).toEqual(expectedError);
    expect(email()('not_an_email_address')).toEqual(expectedError);
    expect(email()('not_an_email_address@')).toEqual(expectedError);
    expect(email()('@not_an_email_address')).toEqual(expectedError);
  });

  test('should return undefined if the provided value is an email address', () => {
    expect(email()('valid@emailaddress.com')).toBeUndefined();
  });
});

describe('the "forbidden" validator', () => {
  test('should return a forbidden error if the provided value equals one of the forbidden values', () => {
    const expectedError = { id: 'forbidden' };
    const fn = forbidden(['mocked_forbidden_value', 'another_mocked_forbidden_value']);

    expect(fn('mocked_forbidden_value')).toEqual(expectedError);
    expect(fn('mocked_forbidden_valu')).toBeUndefined();
    expect(fn('another_mocked_forbidden_value')).toEqual(expectedError);
    expect(fn('another_mocked_forbidden_valu')).toBeUndefined();
    expect(fn('')).toBeUndefined();
  });
});

describe('the "minLength" validator', () => {
  test('should return undefined if the provided value is not a string', () => {
    expect(minLength(2)([])).toBeUndefined();
  });

  test('should return undefined if the provided value has at least the provided number of characters', () => {
    expect(minLength(2)('ab')).toBeUndefined();
  });

  test('should return a minLength error if the provided value has less characters than the provided number of characters', () => {
    expect(minLength(6)('abcde')).toEqual({
      actualLength: 5,
      id: 'minLength',
      minLength: 6,
      tooLittleLength: 1,
    });
  });
});

describe('the "dateMaxUntilToday" validator', () => {
  beforeEach(() => {
    vi.useFakeTimers();

    const today = new Date('2020-01-02');
    vi.setSystemTime(today);
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  test('should return undefined if the provided value is not a string', () => {
    expect(dateMaxUntilToday()([])).toBeUndefined();
  });

  test('should return undefined if the provided date is today or earlier than today', () => {
    expect(dateMaxUntilToday()('2020-01-01')).toBeUndefined();
    expect(dateMaxUntilToday()('2020-01-02 23:59')).toBeUndefined();
  });

  test('should return a dateMaxUntilToday error if the provided date is somewhere after today', () => {
    expect(dateMaxUntilToday()('2020-01-03')).toEqual({ id: 'dateMaxUntilToday' });
  });
});
