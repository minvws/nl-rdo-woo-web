import { describe, it, expect, vi } from 'vitest';
import { mount, type VueWrapper } from '@vue/test-utils';
import InputFile from './InputFile.vue';

describe('the <InputFile /> component', () => {
  interface Options {
    helpText: string;
  }

  const createComponent = (options: Partial<Options> = {}) =>
    mount(InputFile, {
      props: {
        allowMultiple: false,
        enableAutoUpload: false,
        fileLimits: [{ size: 1234 }],
        helpText: options.helpText,
        label: 'Mocked label',
        name: 'mocked_name',
        payload: {},
        tip: 'mocked tip',
        uploadId: 'mocked_upload_id',
        uploadedFileInfo: null,
      },
      global: {
        provide: {
          form: {
            addInput: vi.fn(),
          },
        },
      },
      shallow: true,
    });

  const getFormHelpComponent = (wrapper: VueWrapper) =>
    wrapper.findComponent({ name: 'FormHelp' });

  const getFormLabelComponent = (wrapper: VueWrapper) =>
    wrapper.findComponent({ name: 'FormLabel' });

  const getUploadAreaComponent = (wrapper: VueWrapper) =>
    wrapper.findComponent({ name: 'UploadArea' });

  it('should render the provided label which points to the upload area', () => {
    const component = createComponent();
    const formLabelComponent = getFormLabelComponent(component);
    const uploadAreaComponent = getUploadAreaComponent(component);

    expect(formLabelComponent.props('for')).toBe(
      uploadAreaComponent.props('id'),
    );
  });

  it('should render the provided help text', () => {
    expect(getFormHelpComponent(createComponent()).exists()).toBe(false);

    expect(
      getFormHelpComponent(
        createComponent({ helpText: 'Mocked help text' }),
      ).exists(),
    ).toBe(true);
  });

  it('should display the option to upload files', () => {
    const component = createComponent();
    const uploadAreaComponent = getUploadAreaComponent(component);

    expect(uploadAreaComponent.props()).toEqual(
      expect.objectContaining({
        allowMultiple: false,
        enableAutoUpload: false,
        endpoint: undefined,
        fileLimits: [{ size: 1234 }],
        name: 'mocked_name',
        payload: {},
        tip: 'mocked tip',
        uploadedFileInfo: null,
      }),
    );
  });
});
