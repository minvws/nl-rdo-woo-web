import { describe, expect, test } from 'vitest';
import {
  collectFileLimitLabels,
  collectFileLimitMimeTypes,
  collectFileLimitSizes,
  hasFileUploadLimits,
} from './limits';

describe('The functions regarding file upload limits', () => {
  const limits = [
    { label: 'PDF', mimeTypes: ['application/pdf'], size: 1024 * 1024 },
    { label: 'PNG', mimeTypes: ['image/png'], size: 1024 * 1024 * 5 },
    { label: 'JPG', mimeTypes: ['image/jpeg'], size: 1024 * 1024 * 5 },
    {
      label: 'Image',
      mimeTypes: ['image/jpeg', 'image/png'],
      size: 1024 * 1024 * 5,
    },
  ];

  describe('the "collectFileLimitLabels" function', () => {
    test('should return a list of file labels, sorted alphabetically', () => {
      expect(collectFileLimitLabels(limits)).toEqual([
        'Image',
        'JPG',
        'PDF',
        'PNG',
      ]);
    });
  });

  describe('the "collectFileLimitSizes" function', () => {
    test('should return a list of unique file sizes, sorted in ascending order', () => {
      expect(collectFileLimitSizes(limits)).toEqual([
        1024 * 1024,
        1024 * 1024 * 5,
      ]);
    });
  });

  describe('the "collectFileLimitMimeTypes" function', () => {
    test('should return a list of unique mime types, sorted alphabetically', () => {
      expect(collectFileLimitMimeTypes(limits)).toEqual([
        'application/pdf',
        'image/jpeg',
        'image/png',
      ]);
    });
  });

  describe('the "hasFileUploadLimits" function', () => {
    test('should return a boolean indicating if there are file upload limits', () => {
      expect(hasFileUploadLimits(limits)).toBe(true);
      expect(hasFileUploadLimits([])).toBe(false);
    });
  });
});
