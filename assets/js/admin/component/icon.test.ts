import filePath from '@img/admin/icons.svg';
import * as iconFunctionality from '@js/component';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import { icon } from './icon';

describe('the "icon" function', () => {
  beforeEach(() => {
    vi.spyOn(iconFunctionality, 'icon').mockReturnValue(
      'mocked-icon-component',
    );
  });

  test('should return an icon component with the provided properties', () => {
    const properties: iconFunctionality.IconProperties = {
      name: 'icon-name',
      size: 128,
    };

    expect(icon({ ...properties })).toBe('mocked-icon-component');
    expect(iconFunctionality.icon).toHaveBeenCalledWith({
      ...properties,
      color: 'fill-bhr-dim-gray',
      filePath,
    });
  });
});
