import { createTestFile } from '@js/test';
import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import MismatchedFiles from './MismatchedFiles.vue';

describe('The "MismatchedFiles" component', () => {
  const createTestFiles = () => [
    createTestFile({ name: '1.xlsx' }),
    createTestFile({ name: '2.xlsx' }),
  ];

  const createComponent = (files = createTestFiles(), allowMultiple = false) =>
    mount(MismatchedFiles, {
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

    test('should display a message with the file name saying the type of file does not match its name', () => {
      expect(component.text()).toContain(
        'Het bestand "1.xlsx" is niet opgeslagen, omdat het bestandstype niet overeenkomt met de extensie',
      );
    });

    test('should tell the user what to do next', () => {
      expect(component.text()).toContain(
        'Upload het originele bestand of sla het opnieuw op in het juiste formaat.',
      );
    });

    test('should not display the files list', () => {
      expect(getFilesListComponent(component).exists()).toBe(false);
    });
  });

  describe('when it is allowed to upload multiple files at once', () => {
    const testFiles = createTestFiles();
    const component = createComponent(testFiles, true);

    test('should display a message saying the type of multiple files does not match their names', () => {
      expect(component.text()).toContain(
        'De onderstaande bestanden zijn niet opgeslagen, omdat de bestandstypes niet overeenkomen met de extensie',
      );
    });

    test('should display the files list', () => {
      expect(getFilesListComponent(component).props('files')).toEqual(
        testFiles,
      );
    });
  });
});
