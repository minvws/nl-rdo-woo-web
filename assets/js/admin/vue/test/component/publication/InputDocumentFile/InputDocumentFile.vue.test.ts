import InputDocumentFile from '@admin-fe/component/publication/InputDocumentFile.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "InputDocumentFile" component', () => {
  const createComponent = (displayMaxOneFileMessage = false) => mount(InputDocumentFile, {
    props: {
      displayMaxOneFileMessage,
      uploadedFileInfo: null,
      groupId: 'mocked-group-id',
      value: 'mocked-value',
    },
    shallow: true,
  });

  const getInputFileComponent = (displayMaxOneFileMessage = false) => createComponent(displayMaxOneFileMessage)
    .findComponent({ name: 'InputFile' });

  test('should display a file upload field with a label being "Bestand"', () => {
    expect(getInputFileComponent().props('label')).toBe('Bestand');
  });

  test('should display a message saying only one file can be uploaded when the property "displayMaxOneFileMessage" is true', () => {
    expect(getInputFileComponent().props('helpText')).toBe(undefined);
    expect(getInputFileComponent(true).props('helpText')).toBe('Je kunt maximaal 1 bestand uploaden');
  });

  test('should pass the provided group id', () => {
    expect(getInputFileComponent().props('groupId')).toBe('mocked-group-id');
  });
});
