import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import UploadAreaController from './UploadAreaController.vue';

describe('The "<UploadAreaController />" component', () => {
  const createComponent = () =>
    mount(UploadAreaController, {
      props: {
        allowMultiple: true,
        name: 'mocked-name',
        maxFileSize: 1000000,
      },
      shallow: true,
    });

  const getUploadAreaComponentProps = () =>
    createComponent().findComponent({ name: 'UploadArea' }).props();

  test('should render a <UploadArea /> component', async () => {
    expect(getUploadAreaComponentProps()).toMatchObject({
      allowMultiple: true,
      name: 'mocked-name',
      maxFileSize: 1000000,
    });
  });
});
