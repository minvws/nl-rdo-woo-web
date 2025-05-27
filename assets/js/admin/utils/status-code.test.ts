import { expect, test, describe } from 'vitest';
import { isSuccessStatusCode } from './status-code';

describe('The status code utility', () => {
  test('should return true for a status code in the 200 range', () => {
    const statusCodes = Array.from({ length: 750 }, (_, i) => i + 1);

    statusCodes.forEach((statusCode) => {
      expect(isSuccessStatusCode(statusCode)).toBe(
        statusCode >= 200 && statusCode < 300,
      );
    });
  });
});
