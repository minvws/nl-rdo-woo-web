import { describe, expect, test, vi } from 'vitest';
import { getUrlProperties, isExternalUrl, isValidUrl } from './url';

vi.mock('./browser');

describe('The url utility functions', () => {
  describe('the "getUrlProperties" function', () => {
    test('should return the properties of the provided url, based on the current origin', () => {
      expect(getUrlProperties('/mocked-url')).toEqual(
        new URL('/mocked-url', 'https://mocked-origin.com'),
      );
    });
  });

  describe('the "isExternalUrl" function', () => {
    test('should return true if the provided url has a different origin than the current origin', () => {
      expect(isExternalUrl('/uri-on-this-origin')).toBe(false);
      expect(
        isExternalUrl('https://mocked-origin.com/uri-on-this-origin'),
      ).toBe(false);
      expect(
        isExternalUrl(
          'https://another-mocked-origin.com/uri-on-another-origin',
        ),
      ).toBe(true);
    });
  });

  describe('the "isValidUrl" function', () => {
    test('should return true if the provided url is invalid (an origin is missing)', () => {
      expect(isValidUrl('[]')).toBe(false);
      expect(isValidUrl('123')).toBe(false);
      expect(isValidUrl('---')).toBe(false);
      expect(isValidUrl('/this-uri-needs-an-origin')).toBe(false);
      expect(isValidUrl('https://with-origin.com/this-is-a-valid-url')).toBe(
        true,
      );
    });
  });
});
