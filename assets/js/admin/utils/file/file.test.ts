import { createTestFile } from '@js/test';
import { describe, expect, test } from 'vitest';
import { areFilesEqual, formatExtensions, formatFileSize, getFileTypeByMimeType, getIconNameByMimeType, isValidMaxFileSize } from './file';

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

    test('should return "file-presentation" for presentation mimetypes', () => {
      expect(getIconNameByMimeType('application/mspowerpoint')).toBe('file-presentation');
      expect(getIconNameByMimeType('application/vnd.openxmlformats-officedocument.presentationml.slideshow')).toBe('file-presentation');
      expect(getIconNameByMimeType('application/vnd.oasis.opendocument.presentation')).toBe('file-presentation');
    });

    test('should return "file-text" for text mimetypes', () => {
      expect(getIconNameByMimeType('text/plain')).toBe('file-text');
    });

    test('should return "file-video" for video mimetypes', () => {
      expect(getIconNameByMimeType('video/mp4')).toBe('file-video');
    });

    test('should return "file-word" for word mimetypes', () => {
      expect(getIconNameByMimeType('application/vnd.openxmlformats-officedocument.wordprocessingml.document')).toBe('file-word');
    });

    test('should return "file-xml" for xml mimetypes', () => {
      expect(getIconNameByMimeType('application/rdf+xml')).toBe('file-xml');
    });

    test('should return "file-zip" for zip mimetypes', () => {
      expect(getIconNameByMimeType('application/zip')).toBe('file-zip');
      expect(getIconNameByMimeType('application/x-7z-compressed')).toBe('file-zip');
    });

    test('should return "file-unknown" for unknown mimetypes', () => {
      expect(getIconNameByMimeType('an-unknown-mime-type')).toBe('file-unknown');
    });
  });

  describe('the "getFileTypeByMimeType" function', () => {
    test('should return the correct file type based on the mime type', () => {
      expect(getFileTypeByMimeType('application/acrobat')).toBe('pdf');
      expect(getFileTypeByMimeType('application/vnd.ms-powerpoint')).toBe('presentatie');
      expect(getFileTypeByMimeType('unknown-mime-type')).toBe('onbekend');
    });
  });

  describe('the "areFilesEqual" function', () => {
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

  describe('the "formatExtensions" function', () => {
    test('should order the provided extensions, add a dot and correctly format then', () => {
      expect(formatExtensions(['z-exténsion', '.a-extension', 'b-extension'], 'and')).toBe('.a-extension, .b-extension and .z-exténsion');
    });
  });

  describe('the "isValidMaxFileSize" function', () => {
    test('should return true if the provided max file size is larger than 0', () => {
      expect(isValidMaxFileSize(0)).toBe(false);
      expect(isValidMaxFileSize(-100)).toBe(false);
      expect(isValidMaxFileSize(1)).toBe(true);
      expect(isValidMaxFileSize(500)).toBe(true);
    });
  });
});
