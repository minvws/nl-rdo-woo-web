import { createTestFile } from '@js/test';
import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import MissingHeaderRowFiles from './MissingHeaderRowFiles.vue';

describe('The "MissingHeaderRowFiles" component', () => {
  const createTestFiles = () => [
    createTestFile({ name: '1.xlsx' }),
    createTestFile({ name: '2.xlsx' }),
  ];

  const createComponent = (files = createTestFiles(), allowMultiple = false) =>
    mount(MissingHeaderRowFiles, {
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

    test('should display a message with the file name saying the first row has no column names', () => {
      expect(component.text()).toContain(
        'De eerste rij van het eerste werkblad in "1.xlsx" bevat geen kolomnamen.',
      );
    });

    test('should tell the user what to do next', () => {
      expect(component.text()).toContain(
        'Voeg kolomnamen toe aan de eerste rij en upload het bestand opnieuw.',
      );
    });

    test('should not display the files list', () => {
      expect(getFilesListComponent(component).exists()).toBe(false);
    });
  });

  describe('when it is allowed to upload multiple files at once', () => {
    const testFiles = createTestFiles();
    const component = createComponent(testFiles, true);

    test('should display a message saying the first row of multiple files has no column names', () => {
      expect(component.text()).toContain(
        'De eerste rij van het eerste werkblad in onderstaande bestanden bevat geen kolomnamen.',
      );
    });

    test('should display the files list', () => {
      expect(getFilesListComponent(component).props('files')).toEqual(
        testFiles,
      );
    });
  });
});
