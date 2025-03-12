import { formatFileSize } from '@js/admin/utils';
import { createTestFile } from '@js/test';
import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import FilesList from './FilesList.vue';

describe('The "FilesList" component', () => {
  const createComponent = (files: File[] = []) =>
    mount(FilesList, {
      props: {
        files,
      },
      shallow: true,
    });

  const getListElement = (component: VueWrapper) => component.find('ul');
  const getListItemElements = (component: VueWrapper) =>
    component.findAll('li');

  test('should display a list of the provided files, each item showing the file name and size"', () => {
    const testFiles = [
      createTestFile({ name: '1.txt' }),
      createTestFile({ name: '2.txt' }),
    ];
    const listItemElements = getListItemElements(createComponent(testFiles));

    expect(listItemElements.length).toBe(2);
    expect(listItemElements[0].text()).toBe(
      `1.txt (${formatFileSize(testFiles[0].size)})`,
    );
    expect(listItemElements[1].text()).toBe(
      `2.txt (${formatFileSize(testFiles[1].size)})`,
    );
  });

  test('should not display a list at all (to prevent an empty list) when no files are provided"', () => {
    expect(getListElement(createComponent()).exists()).toBe(false);
  });
});
