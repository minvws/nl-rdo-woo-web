import { describe, expect, test, vi } from 'vitest';
import { formatDate, isDateInvalid, isDateValid } from './date';

vi.mock('./browser');

describe('The date utility functions', () => {
  describe('the "isDateValid" function', () => {
    test('should return true if a valid date is provided', () => {
      expect(isDateValid('one-two-three')).toBe(false);
      expect(isDateValid('---')).toBe(false);
      expect(isDateValid('abcdefg')).toBe(false);

      expect(isDateValid('2020-01-01')).toBe(true);
    });
  });

  describe('the "isDateInvalid" function', () => {
    test('should return true if an invalid date is provided', () => {
      expect(isDateInvalid('one-two-three')).toBe(true);
      expect(isDateInvalid('---')).toBe(true);
      expect(isDateInvalid('abcdefg')).toBe(true);

      expect(isDateInvalid('2020-01-01')).toBe(false);
    });
  });

  describe('the "formatDate" function', () => {
    test('should return the provided date in the provided format', () => {
      const date = '2022-01-02';

      const getExpectedDate = (dateStyle: 'long' | 'medium' | 'short') => new Intl.DateTimeFormat('nl', {
        dateStyle,
      }).format(new Date(date));

      expect(formatDate(date)).toBe(getExpectedDate('medium'));
      expect(formatDate(date, 'long')).toBe(getExpectedDate('long'));
    });
  });
});
