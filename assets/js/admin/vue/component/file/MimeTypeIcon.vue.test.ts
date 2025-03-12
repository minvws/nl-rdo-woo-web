import { getIconNameByMimeType } from '@js/admin/utils';
import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import MimeTypeIcon from './MimeTypeIcon.vue';

describe('The <MimeTypeIcon /> component', () => {
  interface Options {
    mimeType?: string;
    size?: number;
  }

  const createComponent = (options: Partial<Options> = {}) => {
    const { mimeType, size } = options;
    return mount(MimeTypeIcon, {
      props: {
        mimeType,
        size,
      },
      shallow: true,
    });
  };

  const getIconComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'Icon' });

  test('should display an icon based on the provided mimetype with the provided size', () => {
    expect(
      getIconComponent(
        createComponent({ mimeType: 'mocked-mimetype', size: 512 }),
      ).props(),
    ).toMatchObject({
      name: getIconNameByMimeType('mocked-mimetype'),
      size: 512,
    });

    expect(getIconComponent(createComponent()).props()).toMatchObject({
      name: getIconNameByMimeType(''),
    });
  });
});
