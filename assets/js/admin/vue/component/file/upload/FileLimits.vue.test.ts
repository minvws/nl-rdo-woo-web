import type { FileUploadLimit } from '@js/admin/utils/file/interface';
import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import FileLimits from './FileLimits.vue';

describe('The "FileLimits" component', () => {
  const mockedFileLimits: FileUploadLimit[] = [
    {
      label: 'PDF',
      mimeTypes: ['application/pdf'],
      size: 1024 * 1024,
    },
    {
      label: 'Spreadsheet',
      mimeTypes: [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      ],
      size: 1024 * 1024 * 2,
    },
    {
      label: 'PNG',
      mimeTypes: ['image/png'],
      size: 1024 * 1024 * 3,
    },
    {
      label: 'JPG',
      mimeTypes: ['image/jpeg'],
      size: 1024 * 1024 * 3,
    },
  ];

  const createComponent = (
    limits = mockedFileLimits,
    fileOrFiles = 'Bestand',
  ) =>
    mount(FileLimits, {
      props: {
        fileOrFiles,
        id: 'mocked-id',
        limits,
      },
      shallow: true,
    });

  describe('the message about allowed file types', () => {
    test('should display a list of allowed file types with their max size', () => {
      const component = createComponent();
      expect(component.text()).toContain(
        'Bestand van het type PDF (max 1 MB), Spreadsheet (max 2 MB) of JPG, PNG (max 3 MB)',
      );
    });

    test('should display a list of only allowed file types (without their max size) when there is only one max file size defined', () => {
      const component = createComponent(mockedFileLimits.slice(-2));
      expect(component.text()).toContain('Bestand van het type JPG of PNG');
    });

    test('should display a list of only allowed file types (without their max size) when there is no max file size defined', () => {
      const component = createComponent([
        {
          label: 'PNG',
          mimeTypes: ['image/png'],
        },
        {
          label: 'JPG',
          mimeTypes: ['image/jpeg'],
        },
      ]);
      expect(component.text()).toContain('Bestand van het type JPG of PNG');
    });
  });

  describe('the message about the max file size for all allowed file types', () => {
    test('should be displayed when there is exactly one max file size defined', () => {
      const component = createComponent(mockedFileLimits.slice(-2));
      expect(component.text()).toContain('(max 3 MB per bestand)');
    });

    test('should not be displayed when there is more than one max file size defined', () => {
      const component = createComponent();
      expect(component.text()).not.toContain(' per bestand)');
    });

    test('should not be displayed when there no max file size defined', () => {
      const component = createComponent([
        {
          label: 'PNG',
          mimeTypes: ['image/png'],
        },
        {
          label: 'JPG',
          mimeTypes: ['image/jpeg'],
        },
      ]);
      expect(component.text()).not.toContain(' per bestand)');
    });
  });
});
