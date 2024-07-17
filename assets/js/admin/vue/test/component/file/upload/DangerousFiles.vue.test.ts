import DangerousFiles from '@admin-fe/component/file/upload/DangerousFiles.vue';
import { createTestFile } from '@js/test';
import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "DangerousFiles" component', () => {
  const createTestFiles = () => [createTestFile({ name: '1.txt' }), createTestFile({ name: '2.txt' })];

  const createComponent = (files = createTestFiles(), allowMultiple = false) => mount(DangerousFiles, {
    props: {
      allowMultiple,
      files,
    },
  });

  const getFilesListComponent = (component: VueWrapper) => component.findComponent({ name: 'FilesList' });

  test('should display nothing when no files are provided', () => {
    expect(createComponent([]).text()).toBe('');
  });

  describe('when only one file is provided', () => {
    const component = createComponent([createTestFile({ name: '1.txt' })]);

    test('should display a message saying it is invalid', () => {
      expect(component.text()).toContain(
        'Er zijn mogelijk gevaren gevonden in het bestand, het bestand wordt niet opgeslagen. Probeer een ander bestand.',
      );
    });

    test('should not display the files list', () => {
      expect(getFilesListComponent(component).exists()).toBe(false);
    });
  });

  describe('when it is allowed to upload multiple files at once', () => {
    const testFiles = createTestFiles();
    const component = createComponent(testFiles, true);

    test('should display a message saying multiple files are invalid', () => {
      expect(component.text()).toContain(
        'Er zijn mogelijke gevaren gevonden in de onderstaande bestanden. Ze worden daarom niet opgeslagen. Probeer een ander bestand.',
      );
    });

    test('should display the files list', () => {
      expect(getFilesListComponent(component).props('files')).toEqual(testFiles);
    });
  });
});
