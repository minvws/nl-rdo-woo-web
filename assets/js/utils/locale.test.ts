import { beforeEach, describe, expect, test, vi } from 'vitest';
import { getCurrentLanguage, getCurrentLocale } from './locale';

let mockedLanguageAttributeValue: string | undefined;

vi.mock('./browser', () => ({
  getDocument: () => ({
    documentElement: {
      getAttribute: vi.fn().mockReturnValue(mockedLanguageAttributeValue),
    },
  }),
}));

describe('The locale utility functions', () => {
  beforeEach(() => {
    mockedLanguageAttributeValue = 'nl';
  });

  describe('the "getCurrentLanguage" function', () => {
    test('should return the language defined on the html element', () => {
      expect(getCurrentLanguage()).toBe('nl');

      mockedLanguageAttributeValue = 'mocked-lang';
      expect(getCurrentLanguage()).toBe('mocked-lang');
    });

    test('should return "nl" if no language is defined on the html element', () => {
      mockedLanguageAttributeValue = undefined;
      expect(getCurrentLanguage()).toBe('nl');
    });
  });

  describe('the "getCurrentLocale" function', () => {
    test('should return the English locale if the language defined on the html element equals "en"', () => {
      mockedLanguageAttributeValue = 'en';
      expect(getCurrentLocale()).toBe('en-GB');
    });

    test('should return "nl-NL" if the language defined on the html element is unuqual to "nl"', () => {
      mockedLanguageAttributeValue = undefined;
      expect(getCurrentLocale()).toBe('nl-NL');
    });
  });
});
