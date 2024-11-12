import InvalidFiles from '@admin-fe/component/file/upload/InvalidFiles.vue';
import { formatFileSize, formatList } from '@js/admin/utils';
import { createTestFile } from '@js/test';
import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "InvalidFiles" component', () => {
  const mockedAllowedFileTypes = ['mocked-extension-1', 'mocked-extension-2'];
  const mockedAllowedMimeTypes = ['mocked-mime-type-1', 'mocked-mime-type-2'];
  const mockedFiles = [
    createTestFile({ name: 'mocked-file-1' }),
    createTestFile({ name: 'mocked-file-2' }),
  ];
  const mockedMaxFileSize = 1000;

  const createComponent = (files: File[] = mockedFiles) =>
    mount(InvalidFiles, {
      props: {
        allowedFileTypes: mockedAllowedFileTypes,
        allowedMimeTypes: mockedAllowedMimeTypes,
        files: files.map((file, index) => ({ file, index })),
        maxFileSize: mockedMaxFileSize,
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
        `Het bestand "mocked-file-1" (${formatFileSize(mockedInvalidFile.size)}) werd genegeerd omdat het invalide is`,
      );
    });

    test('should not display a list of files', () => {
      const component = createComponent([mockedFiles[0]]);
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

  test('should display the allowed file types', () => {
    const component = createComponent();
    expect(component.text()).toContain(
      `Alleen bestanden van het type ${formatList(mockedAllowedFileTypes, 'en')} zijn toegestaan.`,
    );
  });

  test('should display the max allowed file size', () => {
    const component = createComponent();
    expect(component.text()).toContain(
      `De maximale bestandsgrootte per bestand is ${formatFileSize(mockedMaxFileSize)}.`,
    );
  });
});
