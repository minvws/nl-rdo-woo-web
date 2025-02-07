import InputDocumentFile from '@admin-fe/component/publication/InputDocumentFile.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "InputDocumentFile" component', () => {
  const allowedFileTypes = ['mocked-extension-1', 'mocked-extension-2'];
  const allowedMimeTypes = ['mocked/mime-type-1', 'mocked/mime-type-2'];

  const createComponent = (displayMaxOneFileMessage = false) =>
    mount(InputDocumentFile, {
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

  const getInputFileComponent = (displayMaxOneFileMessage = false) =>
    createComponent(displayMaxOneFileMessage).findComponent({
      name: 'InputFile',
    });

  test('should display a file upload field with a label being "Bestand"', () => {
    expect(getInputFileComponent().props('label')).toBe('Bestand');
  });

  test('should display a message saying only one file can be uploaded when the property "displayMaxOneFileMessage" is true', () => {
    expect(getInputFileComponent().props('helpText')).toBe(undefined);
    expect(getInputFileComponent(true).props('helpText')).toBe(
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
});
