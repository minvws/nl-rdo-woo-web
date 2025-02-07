import { createTestFile } from '@js/test/file';
import { describe, expect, test } from 'vitest';
import { validateFiles } from './validate';

describe('The "validateFiles" function', () => {
  test('should return files which are too large', () => {
    const { invalidSize } = validateFiles(
      [
        createTestFile({ size: 1024, name: 'valid-size' }),
        createTestFile({ size: 1025, name: 'invalid-size' }),
      ],
      [],
      1024,
    );

    expect(invalidSize).toHaveLength(1);
    expect(invalidSize[0].name).toBe('invalid-size');
  });

  test('should return files which have an invalid mime type', () => {
    const { invalidType } = validateFiles(
      [
        createTestFile({ type: 'image/png', name: 'valid-type' }),
        createTestFile({ type: 'image/jpeg', name: 'invalid-type' }),
      ],
      ['image/png'],
    );

    expect(invalidType).toHaveLength(1);
    expect(invalidType[0].name).toBe('invalid-type');
  });

  test('should return files which are valid', () => {
    const { valid } = validateFiles(
      [
        createTestFile({ size: 1024, name: 'valid-size', type: 'image/png' }),
        createTestFile({ size: 1025, name: 'invalid-size', type: 'image/png' }),

        createTestFile({ type: 'image/png', name: 'valid-type' }),
        createTestFile({ type: 'image/jpeg', name: 'invalid-type' }),
      ],
      ['image/png'],
      1024,
    );

    expect(valid).toHaveLength(2);
    expect(valid[0].name).toBe('valid-size');
    expect(valid[1].name).toBe('valid-type');
  });
});
