import UploadArea from '@admin-fe/component/file/upload/UploadArea.vue';
import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "UploadArea" component', () => {
  const mockedAllowedFileTypes = ['mocked-extension-1', 'mocked-extension-2'];
  const mockedMaxFileSize = 1000;
  const mockedAllowedMimeTypes = ['mocked-mime-type-1', 'mocked-mime-type-2'];

  const createComponent = () =>
    mount(UploadArea, {
      props: {
        allowedFileTypes: mockedAllowedFileTypes,
        allowedMimeTypes: mockedAllowedMimeTypes,
        allowMultiple: true,
        enableAutoUpload: false,
        endpoint: 'mocked-upload-endpoint',
        groupId: 'mocked-group-id',
        id: 'mocked-id',
        maxFileSize: mockedMaxFileSize,
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
    expect(childComponent.props('allowedFileTypes')).toEqual(
      mockedAllowedFileTypes,
    );
    expect(childComponent.props('allowedMimeTypes')).toEqual(
      mockedAllowedMimeTypes,
    );
  });

  test('should display a list of posibly dangerous files', () => {
    const component = createComponent();
    const childComponent = component.findComponent({ name: 'DangerousFiles' });

    expect(childComponent.exists()).toBe(true);
    expect(childComponent.props('allowMultiple')).toBe(true);
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
