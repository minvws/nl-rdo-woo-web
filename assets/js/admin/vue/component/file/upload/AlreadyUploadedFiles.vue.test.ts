import { createTestFile } from '@js/test';
import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import AlreadyUploadedFiles from './AlreadyUploadedFiles.vue';

describe('The "AlreadyUploadedFiles" component', () => {
  const createTestFiles = () => [
    createTestFile({ name: '1.txt' }),
    createTestFile({ name: '2.txt' }),
  ];

  const createComponent = (files = createTestFiles()) =>
    mount(AlreadyUploadedFiles, {
      props: {
        files,
      },
    });

  const getFilesListComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'FilesList' });

  test('should display nothing when no files are provided', () => {
    expect(createComponent([]).text()).toBe('');
  });

  describe('when only one file is provided', () => {
    const component = createComponent([createTestFile({ name: '1.txt' })]);

    test('should display the file name and a message saying it is invalid', () => {
      expect(component.text()).toContain('1.txt');
      expect(component.text()).toContain(
        'werd genegeerd omdat het al geüpload is',
      );
    });

    test('should not display the files list', () => {
      expect(getFilesListComponent(component).exists()).toBe(false);
    });
  });

  describe('when more than one file is provided', () => {
    const testFiles = createTestFiles();
    const component = createComponent(testFiles);

    test('should display a message saying multiple files were already uploaded', () => {
      expect(component.text()).toContain(
        'De volgende bestanden werden genegeerd omdat ze al geüpload zijn',
      );
    });

    test('should display the files list', () => {
      expect(getFilesListComponent(component).props('files')).toEqual(
        testFiles,
      );
    });
  });
});
