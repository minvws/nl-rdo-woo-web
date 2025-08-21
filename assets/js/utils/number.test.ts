import { describe, expect, test, vi } from 'vitest';
import { formatNumber } from './number';

vi.mock('./locale', () => ({
  getCurrentLocale: () => 'nl-NL',
}));

describe('The number utility functions', () => {
  describe('the "formatNumber" function', () => {
    test('should display a provided number with at most 2 fractional digits and in the current locale', () => {
      expect(formatNumber(1000)).toBe(
        new Intl.NumberFormat('nl-NL', {
          maximumFractionDigits: 2,
        }).format(1000),
      );
    });
  });
});
