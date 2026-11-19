import { createTestFile } from '@js/test';
import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import MultipleWorksheetsFiles from './MultipleWorksheetsFiles.vue';

describe('The "MultipleWorksheetsFiles" component', () => {
  const createTestFiles = () => [
    createTestFile({ name: '1.xlsx' }),
    createTestFile({ name: '2.xlsx' }),
  ];

  const createComponent = (files = createTestFiles(), allowMultiple = false) =>
    mount(MultipleWorksheetsFiles, {
      props: {
        allowMultiple,
        files,
      },
    });

  const getFilesListComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'FilesList' });

  test('should display nothing when no files are provided', () => {
    expect(createComponent([]).text()).toBe('');
  });

  describe('when only one file is provided', () => {
    const component = createComponent([createTestFile({ name: '1.xlsx' })]);

    test('should display a message with the file name saying the file contains multiple worksheets', () => {
      expect(component.text()).toContain(
        'Het bestand "1.xlsx" bevat meerdere werkbladen.',
      );
    });

    test('should tell the user what to do next', () => {
      expect(component.text()).toContain(
        'Verwijder de overige werkbladen, zodat er één werkblad overblijft, en upload het bestand opnieuw.',
      );
    });

    test('should not display the files list', () => {
      expect(getFilesListComponent(component).exists()).toBe(false);
    });
  });

  describe('when it is allowed to upload multiple files at once', () => {
    const testFiles = createTestFiles();
    const component = createComponent(testFiles, true);

    test('should display a message saying multiple files contain multiple worksheets', () => {
      expect(component.text()).toContain(
        'De onderstaande bestanden bevatten meerdere werkbladen.',
      );
    });

    test('should display the files list', () => {
      expect(getFilesListComponent(component).props('files')).toEqual(
        testFiles,
      );
    });
  });
});
