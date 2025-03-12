import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import InputFileUpload from './InputFileUpload.vue';
import { createTestFile } from '@js/test';

describe('The "InputFileUpload" component', () => {
  const allowedFileTypes = ['mocked-extension-1', 'mocked-extension-2'];
  const allowedMimeTypes = ['mocked/mime-type-1', 'mocked/mime-type-2'];

  const createComponent = (displayMaxOneFileMessage = false) =>
    mount(InputFileUpload, {
      props: {
        allowedFileTypes,
        allowedMimeTypes,
        displayMaxOneFileMessage,
        fileInfo: null,
        groupId: 'mocked-group-id',
        value: 'mocked-value',
      },
      shallow: true,
    });

  const getInputFileComponent = (component = createComponent()) =>
    component.findComponent({ name: 'InputFile' });

  test('should display a file upload field with a label being "Bestand"', () => {
    expect(getInputFileComponent().props('label')).toBe('Bestand');
  });

  test('should display a message saying only one file can be uploaded when the property "displayMaxOneFileMessage" is true', () => {
    expect(getInputFileComponent().props('helpText')).toBe(undefined);
    expect(getInputFileComponent(createComponent(true)).props('helpText')).toBe(
      'Je kunt maximaal 1 bestand uploaden',
    );
  });

  test('should pass a payload containing the provided group id', () => {
    expect(getInputFileComponent().props('payload')).toEqual({
      groupId: 'mocked-group-id',
    });
  });

  test('should pass the provided allowed mime types', () => {
    expect(getInputFileComponent().props('allowedMimeTypes')).toEqual(
      allowedMimeTypes,
    );
  });

  test('should pass the provided allowed extensions', () => {
    expect(getInputFileComponent().props('allowedFileTypes')).toEqual(
      allowedFileTypes,
    );
  });

  test('should emit an "uploaded" event when a file is uploaded', async () => {
    const component = createComponent();
    expect(component.emitted('uploaded')).toBeFalsy();

    const mockedFile = createTestFile();
    await getInputFileComponent(component).vm.$emit('uploaded', mockedFile);

    expect(component.emitted('uploaded')?.[0]).toEqual([mockedFile]);
  });

  test('should emit an "uploadError" event when an errors occurs while uploading a file', async () => {
    const component = createComponent();
    expect(component.emitted('uploadError')).toBeFalsy();

    await getInputFileComponent(component).vm.$emit('uploadError');
    expect(component.emitted('uploadError')).toBeTruthy();
  });
});
