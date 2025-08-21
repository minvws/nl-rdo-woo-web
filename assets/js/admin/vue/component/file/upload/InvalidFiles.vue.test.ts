import { createTestFile } from '@js/test';
import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import InvalidFiles from './InvalidFiles.vue';

describe('The "InvalidFiles" component', () => {
  const mockedFiles = [
    createTestFile({ name: 'mocked-file-1' }),
    createTestFile({ name: 'mocked-file-2' }),
  ];

  const mockedFileLimits = [
    {
      size: 1000,
      mimeTypes: ['mocked-mime-type-1', 'mocked-mime-type-2'],
      label: 'mocked-label',
    },
  ];

  const createComponent = (
    files: File[] = mockedFiles,
    haveInvalidSize = false,
    limits = mockedFileLimits,
  ) =>
    mount(InvalidFiles, {
      props: {
        files,
        limits,
        haveInvalidSize,
      },
      shallow: true,
      global: {
        stubs: {
          Alert: false,
        },
      },
    });

  const getFilesListComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'FilesList' });

  describe('when only one invalid file is provided]', () => {
    test('should mention that the file name is invalid', () => {
      const mockedInvalidFile = mockedFiles[0];
      const component = createComponent([mockedInvalidFile]);

      expect(component.text()).toContain(
        'Het bestand "mocked-file-1" werd genegeerd omdat het van een ongeldig type is',
      );
    });

    test('should not display a list of files', () => {
      const component = createComponent([mockedFiles[0]], false);
      const listComponent = getFilesListComponent(component);

      expect(listComponent.exists()).toBe(false);
    });
  });

  describe('when more than one invalid file is provided', () => {
    test('should display the invalid files in a list', () => {
      const component = createComponent(mockedFiles);
      const listComponent = getFilesListComponent(component);

      expect(listComponent.exists()).toBe(true);
      expect(listComponent.props('files')).toEqual(mockedFiles);
    });
  });

  test('should display the allowed file types when the provided files have an invalid type', () => {
    const expectedText =
      'Alleen bestanden van het type mocked-label zijn toegestaan.';

    expect(createComponent(undefined, false).text()).toContain(expectedText);
    expect(createComponent(undefined, true).text()).not.toContain(expectedText);
  });

  test('should display the max allowed file size when the provided files have an invalid size', () => {
    const limits = [
      {
        size: 1024 * 1024,
        mimeTypes: ['mocked-mime-type-1'],
        label: 'mocked-label',
      },
      {
        size: 1024 * 1024,
        mimeTypes: ['mocked-mime-type-2'],
        label: 'mocked-label-2',
      },
      {
        size: 1024 * 1024 * 2,
        mimeTypes: ['mocked-mime-type-3'],
        label: 'mocked-label-3',
      },
    ];

    const expectedText =
      'De maximale bestandsgrootte per bestand is 1 MB (mocked-label, mocked-label-2) of 2 MB (mocked-label-3)';

    expect(createComponent(undefined, true, limits).text()).toContain(
      expectedText,
    );
    expect(createComponent(undefined, false, limits).text()).not.toContain(
      expectedText,
    );
  });

  test('should display nothing when no files are provided', () => {
    expect(createComponent([]).text()).toBe('');
  });
});
