import SelectedFile from './SelectedFile.vue';
import { mount, type VueWrapper } from '@vue/test-utils';
import { describe, test, expect } from 'vitest';
import { UPLOAD_AREA_ENDPOINT } from './static';

describe('The <SelectedFile /> component', () => {
  interface Options {
    enableAutoUpload?: boolean;
  }

  const createComponent = (options: Partial<Options> = {}) => {
    const { enableAutoUpload = false } = options;
    const file = new File(['mocked content'], 'mock-file.txt', {
      type: 'mocked/mime-type',
    });
    Object.defineProperty(file, 'size', { value: 123456 });
    return mount(SelectedFile, {
      props: {
        enableAutoUpload,
        file,
        fileId: 'mocked-file-id',
      },
      global: {
        provide: {
          [UPLOAD_AREA_ENDPOINT]: 'mocked-upload-endpoint',
        },
        stubs: {
          Collapsible: false,
        },
      },
      shallow: true,
    });
  };

  const getMimeTypeIconComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'MimeTypeIcon' });

  test('should render its content in an <li> element', () => {
    const component = createComponent();

    expect(component.element.tagName).toBe('LI');
  });

  test('should render the file name', () => {
    const component = createComponent();

    expect(component.html()).toContain('mock-file.txt');
  });

  test('should render an icon displaying the file type', () => {
    const component = createComponent();

    expect(getMimeTypeIconComponent(component).props('mimeType')).toBe(
      'mocked/mime-type',
    );
  });
});
