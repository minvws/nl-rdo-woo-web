import SelectedFiles from './SelectedFiles.vue';
import { mount, type VueWrapper } from '@vue/test-utils';
import { describe, test, expect } from 'vitest';
import { createTestFile } from '@js/test';

describe('The <SelectedFiles /> component', () => {
  interface Options {
    allowMultiple: boolean;
    enableAutoUpload: boolean;
    files: Map<string, File>;
  }

  const mockedFiles = new Map([
    ['1', createTestFile({ name: '1.txt' })],
    ['2', createTestFile({ name: '2.txt' })],
  ]);

  const createComponent = (options: Partial<Options> = {}) => {
    const {
      allowMultiple = true,
      enableAutoUpload = true,
      files = mockedFiles,
    } = options;

    return mount(SelectedFiles, {
      props: {
        allowMultiple,
        enableAutoUpload,
        files,
        name: 'mocked-name',
        payload: {
          mocked: 'payload',
        },
      },
      global: {
        stubs: {
          SkipLink: false,
        },
      },
      shallow: true,
    });
  };

  const getSelectedFileComponents = (component: VueWrapper) =>
    component.findAllComponents({ name: 'SelectedFile' });

  const getSkipLinkComponents = (component: VueWrapper) =>
    component.findAllComponents({ name: 'SkipLink' });

  test('should display the provided files', () => {
    const selectedFileComponents = getSelectedFileComponents(createComponent());

    expect(selectedFileComponents.length).toBe(2);
    expect(selectedFileComponents[0].props()).toMatchObject({
      enableAutoUpload: true,
      file: mockedFiles.get('1'),
      fileId: '1',
      payload: {
        mocked: 'payload',
      },
    });
  });

  test('should display skip links above and below the list of files', () => {
    const skipLinkComponents = getSkipLinkComponents(createComponent());

    expect(skipLinkComponents.length).toBe(2);
    expect(skipLinkComponents[0].html()).toContain(
      'Naar einde van lijst met te uploaden bestanden',
    );
    expect(skipLinkComponents[1].html()).toContain(
      'Naar begin van lijst met te uploaden bestanden',
    );
  });

  test('should display a message when no files are selected', () => {
    const component = createComponent({ files: new Map() });

    expect(component.html()).toContain('Geen bestanden geselecteerd');
  });
});
