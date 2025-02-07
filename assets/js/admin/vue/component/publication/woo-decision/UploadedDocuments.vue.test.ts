import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import UploadedDocuments from './UploadedDocuments.vue';

describe('The "UploadDocuments" component', () => {
  const createComponent = (withFiles: boolean = true) => {
    return mount(UploadedDocuments, {
      props: {
        files: withFiles
          ? [
              {
                id: '1',
                name: '1.pdf',
                mimeType: 'application/pdf',
              },
              {
                id: '2',
                name: '2.text',
                mimeType: 'text/plain',
              },
            ]
          : undefined,
      },
      shallow: true,
    });
  };

  test('should display the mime type icon for each file', () => {
    const mimeTypeIconcomponents = createComponent().findAllComponents({
      name: 'MimeTypeIcon',
    });

    expect(mimeTypeIconcomponents).toHaveLength(2);
    expect(mimeTypeIconcomponents[0].props()).toMatchObject({
      mimeType: 'application/pdf',
    });
    expect(mimeTypeIconcomponents[1].props()).toMatchObject({
      mimeType: 'text/plain',
    });
  });

  test('should display the file name of each file', () => {
    const html = createComponent().html();

    expect(html).toContain('1.pdf');
    expect(html).toContain('2.text');
  });

  test('should display a checkmark icon for each file', () => {
    const iconComponents = createComponent().findAllComponents({
      name: 'Icon',
    });

    expect(iconComponents).toHaveLength(2);
    expect(iconComponents[0].props()).toMatchObject({
      name: 'check-rounded-filled',
    });
    expect(iconComponents[1].props()).toMatchObject({
      name: 'check-rounded-filled',
    });
  });

  test('should display nothing when there are no files', () => {
    expect(createComponent(false).text()).toBe('');
  });
});
