import { describe, expect, test } from 'vitest';
import { areFilesEqual, formatFileSize, getIconNameByMimeType } from './file';

describe('The file utility functions', () => {
  describe('the "formatFileSize" function', () => {
    test('should format the provided file size in bytes to a human readable format', () => {
      expect(formatFileSize(0)).toBe('0 Bytes');
      expect(formatFileSize(1)).toBe('1 Bytes');
      expect(formatFileSize(1024)).toBe('1 KB');
      expect(formatFileSize(1024 * 1024)).toBe('1 MB');
      expect(formatFileSize(1024 * 1024 * 1024)).toBe('1 GB');
    });

    test('should format the provided file size in two decimals when necessary', () => {
      expect(formatFileSize(1024 * 1.5)).toBe('1,5 KB');
      expect(formatFileSize(1024 * 1024 * 8.7654)).toBe('8,77 MB');
      expect(formatFileSize(1024 * 1024 * 1024 * 1.2345)).toBe('1,23 GB');
    });
  });

  describe('the "getIconNameByMimeType" function', () => {
    test('should return "file-csv" for csv mimetypes', () => {
      expect(getIconNameByMimeType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')).toBe('file-csv');
    });

    test('should return "file-pdf" for pdf mimetypes', () => {
      expect(getIconNameByMimeType('application/pdf')).toBe('file-pdf');
    });

    test('should return "file-video" for video mimetypes', () => {
      expect(getIconNameByMimeType('video/mp4')).toBe('file-video');
    });

    test('should return "file-word" for word mimetypes', () => {
      expect(getIconNameByMimeType('application/vnd.openxmlformats-officedocument.wordprocessingml.document')).toBe('file-word');
    });

    test('should return "file-zip" for zip mimetypes', () => {
      expect(getIconNameByMimeType('application/zip')).toBe('file-zip');
      expect(getIconNameByMimeType('application/x-7z-compressed')).toBe('file-zip');
    });

    test('should return "file-unknown" for unknown mimetypes', () => {
      expect(getIconNameByMimeType('an-unknown-mime-type')).toBe('file-unknown');
    });
  });

  describe('the "areFilesEqual" function', () => {
    interface TestFileProperties {
      lastModified?: Date;
      name?: string;
      type?: string;
    }

    const createTestFile = (properties: TestFileProperties = {}) => {
      const { name = 'file.txt', lastModified = new Date(2020, 1, 2), type = 'text/plain' } = properties;
      return new File([''], name, { lastModified: lastModified.getTime(), type });
    };

    test('should return true when the provided files are equal', () => {
      expect(areFilesEqual(createTestFile(), createTestFile())).toBe(true);
    });

    test('should return false when the provided files are not equal', () => {
      const file1 = createTestFile();
      const file2 = createTestFile({ lastModified: new Date(2020, 1, 3) });
      const file3 = createTestFile({ name: 'some-file.text' });
      const file4 = createTestFile({ type: 'application/pdf' });

      expect(areFilesEqual(file1, file2)).toBe(false);
      expect(areFilesEqual(file1, file3)).toBe(false);
      expect(areFilesEqual(file1, file4)).toBe(false);
      expect(areFilesEqual(file2, file4)).toBe(false);
    });
  });
});
