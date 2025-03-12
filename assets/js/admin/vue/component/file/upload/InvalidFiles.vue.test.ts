import { formatFileSize, formatList } from '@js/admin/utils';
import { createTestFile } from '@js/test';
import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import InvalidFiles from './InvalidFiles.vue';

describe('The "InvalidFiles" component', () => {
  const mockedAllowedFileTypes = ['mocked-extension-1', 'mocked-extension-2'];
  const mockedAllowedMimeTypes = ['mocked-mime-type-1', 'mocked-mime-type-2'];
  const mockedFiles = [
    createTestFile({ name: 'mocked-file-1' }),
    createTestFile({ name: 'mocked-file-2' }),
  ];
  const mockedMaxFileSize = 1000;

  const createComponent = (files: File[] = mockedFiles, isTypeError = true) =>
    mount(InvalidFiles, {
      props: {
        allowedFileTypes: isTypeError ? mockedAllowedFileTypes : [],
        allowedMimeTypes: isTypeError ? mockedAllowedMimeTypes : [],
        files,
        maxFileSize: isTypeError ? undefined : mockedMaxFileSize,
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
    const expectedText = `Alleen bestanden van het type ${formatList(
      mockedAllowedFileTypes,
      'en',
    )} zijn toegestaan.`;

    expect(createComponent().text()).toContain(expectedText);
    expect(createComponent(undefined, false).text()).not.toContain(
      expectedText,
    );
  });

  test('should display the max allowed file size when the provided files have an invalid size', () => {
    const expectedText = `De maximale bestandsgrootte per bestand is ${formatFileSize(
      mockedMaxFileSize,
    )}.`;

    expect(createComponent(undefined, false).text()).toContain(expectedText);
    expect(createComponent(undefined, true).text()).not.toContain(expectedText);
  });

  test('should display nothing when no files are provided', () => {
    expect(createComponent([]).text()).toBe('');
  });
});
