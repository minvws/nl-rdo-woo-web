import { createTestFile } from '@js/test/file';
import { describe, expect, test } from 'vitest';
import { validateFiles } from './validate';

describe('The "validateFiles" function', () => {
  test('should return files which are too large', () => {
    let invalidSize = [];
    ({ invalidSize } = validateFiles(
      [
        createTestFile({ size: 1024, type: 'image/png', name: 'valid-size' }),
        createTestFile({ size: 1025, type: 'image/png', name: 'invalid-size' }),
        createTestFile({
          size: 1024 * 2,
          type: 'image/jpg',
          name: 'valid-size',
        }),
        createTestFile({
          size: 1024 * 2 + 1,
          type: 'image/jpg',
          name: 'invalid-size',
        }),
      ],
      [
        { size: 1024, mimeTypes: ['image/png'], label: 'PNG' },
        { size: 1024 * 2, mimeTypes: ['image/jpg'], label: 'JPG' },
      ],
    ));

    expect(invalidSize).toHaveLength(2);
    expect(invalidSize[0]).toMatchObject({
      name: 'invalid-size',
      size: 1025,
      type: 'image/png',
    });
    expect(invalidSize[1]).toMatchObject({
      name: 'invalid-size',
      size: 1024 * 2 + 1,
      type: 'image/jpg',
    });

    ({ invalidSize } = validateFiles(
      [createTestFile({ size: 1024, type: 'image/png', name: 'valid-size' })],
      [],
    ));

    expect(invalidSize).toHaveLength(0);
  });

  test('should return files which have an invalid mime type', () => {
    const { invalidType } = validateFiles(
      [
        createTestFile({ type: 'image/png', name: 'valid-type' }),
        createTestFile({ type: 'image/jpeg', name: 'invalid-type' }),
        createTestFile({
          type: '',
          name: 'also-valid-type-because-of-firefox-bug',
        }),
      ],
      [{ mimeTypes: ['image/png'], label: 'PNG' }],
    );

    expect(invalidType).toHaveLength(1);
    expect(invalidType[0].name).toBe('invalid-type');
  });

  test('should return files which are valid', () => {
    let valid = [];
    ({ valid } = validateFiles(
      [
        createTestFile({ size: 1024, name: 'valid-size', type: 'image/png' }),
        createTestFile({ size: 1025, name: 'invalid-size', type: 'image/png' }),

        createTestFile({ type: 'image/png', name: 'valid-type' }),
        createTestFile({ type: 'image/jpeg', name: 'invalid-type' }),
      ],
      [{ size: 1024, mimeTypes: ['image/png'], label: 'PNG' }],
    ));

    expect(valid).toHaveLength(2);
    expect(valid[0].name).toBe('valid-size');
    expect(valid[1].name).toBe('valid-type');

    ({ valid } = validateFiles(
      [
        createTestFile({ size: 1024, name: 'valid', type: 'image/png' }),
        createTestFile({ type: 'some-weird-mime-type', name: 'also-valid' }),
      ],
      [],
    ));

    expect(valid).toHaveLength(2);
    expect(valid[0].name).toBe('valid');
    expect(valid[1].name).toBe('also-valid');
  });
});
