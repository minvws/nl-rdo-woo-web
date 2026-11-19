import { VueWrapper, mount } from '@vue/test-utils';
import { UploadValidationErrorReason } from '@js/admin/utils';
import { describe, expect, test } from 'vitest';
import UploadArea from './UploadArea.vue';

describe('The "UploadArea" component', () => {
  const mockedMaxFileSize = 1000;
  const mockedAllowedMimeTypes = ['mocked-mime-type-1', 'mocked-mime-type-2'];
  const mockedFileLimits = [
    {
      size: mockedMaxFileSize,
      mimeTypes: mockedAllowedMimeTypes,
      label: 'mocked-label',
    },
  ];

  const createComponent = ({ enableAutoUpload = false } = {}) =>
    mount(UploadArea, {
      props: {
        allowMultiple: true,
        enableAutoUpload,
        endpoint: 'mocked-upload-endpoint',
        groupId: 'mocked-group-id',
        fileLimits: mockedFileLimits,
        id: 'mocked-id',
        name: 'mocked-name',
        tip: 'mocked-tip',
        uploadedFileInfo: null,
      },
      shallow: true,
    });

  const getInputElement = (component: VueWrapper) =>
    component.find('input[type="file"]');

  describe('the file upload field', () => {
    test('should have the correct attributes', () => {
      const component = createComponent();
      const inputElement = getInputElement(component);

      expect(inputElement.attributes('accept')).toBe(
        mockedAllowedMimeTypes.join(','),
      );
      expect(inputElement.attributes('multiple')).toBeFalsy();
      expect(inputElement.attributes('name')).toBe('mocked-name');
      expect(inputElement.attributes('id')).toBe('mocked-id');
    });
  });

  test('should display a list of invalid files', () => {
    const component = createComponent();
    const childComponent = component.findComponent({ name: 'InvalidFiles' });

    expect(childComponent.exists()).toBe(true);
    expect(childComponent.props('limits')).toEqual(mockedFileLimits);
  });

  test('should display a list of posibly dangerous files', () => {
    const component = createComponent();
    const childComponent = component.findComponent({ name: 'DangerousFiles' });

    expect(childComponent.exists()).toBe(true);
    expect(childComponent.props('allowMultiple')).toBe(true);
  });

  describe('when an upload fails', () => {
    const emitUploadError = (
      component: VueWrapper,
      validationErrorReason: string | null,
    ) => {
      const file = new File(['content'], 'mocked-file.pdf');
      component
        .findComponent({ name: 'SelectedFiles' })
        .vm.$emit('uploadError', 'mocked-file-id', file, {
          validationErrorReason,
        });

      return file;
    };

    const getDangerousFiles = (component: VueWrapper) =>
      component.findComponent({ name: 'DangerousFiles' }).props('files');

    test('should list the file as dangerous when the reason is an unsafe file', async () => {
      const component = createComponent({ enableAutoUpload: true });

      const file = emitUploadError(
        component,
        UploadValidationErrorReason.UnsafeFile,
      );
      await component.vm.$nextTick();

      expect(getDangerousFiles(component)).toEqual([file]);
    });

    test('should list the file as mismatched when the type of file does not match its extension', async () => {
      const component = createComponent({ enableAutoUpload: true });

      const file = emitUploadError(
        component,
        UploadValidationErrorReason.ExtensionMimeTypeMismatch,
      );
      await component.vm.$nextTick();

      expect(
        component.findComponent({ name: 'MismatchedFiles' }).props('files'),
      ).toEqual([file]);
      expect(getDangerousFiles(component)).toEqual([]);
    });

    test.each([
      UploadValidationErrorReason.InvalidExtension,
      UploadValidationErrorReason.FileSizeExceeded,
      null,
    ])(
      'should not list the file as dangerous when the reason is %s',
      async (validationErrorReason) => {
        const component = createComponent({ enableAutoUpload: true });

        emitUploadError(component, validationErrorReason);
        await component.vm.$nextTick();

        expect(getDangerousFiles(component)).toEqual([]);
      },
    );
  });

  test('should display the provided tip', () => {
    const component = createComponent();

    expect(component.text()).toContain('mocked-tip');
  });

  test('should display a text saying files can be dragged into the area', () => {
    const component = createComponent();

    expect(component.text()).toContain(
      'Bestanden selecteren  of hierin slepen',
    );
  });
});
