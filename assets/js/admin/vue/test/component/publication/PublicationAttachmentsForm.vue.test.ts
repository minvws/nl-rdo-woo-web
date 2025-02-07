import PublicationAttachmentsForm from '@admin-fe/component/publication/PublicationAttachmentsForm.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "PublicationAttachmentsForm" component', () => {
  interface CreateComponentOptions {
    allowedFileTypes: string[];
    allowedMimeTypes: string[];
    documentLanguageOptions: string[];
    documentTypeOptions: string[];
    groundOptions: string[];
  }

  const createComponent = (options: Partial<CreateComponentOptions> = {}) => {
    const {
      allowedFileTypes = [],
      allowedMimeTypes = [],
      documentLanguageOptions = [],
      documentTypeOptions = [],
      groundOptions = [],
    } = options;

    return mount(PublicationAttachmentsForm, {
      props: {
        allowedFileTypes,
        allowedMimeTypes,
        attachment: {
          id: 'mocked-id',
          internalReference: 'mocked-internal-reference',
          language: 'mocked-language',
          name: 'mocked-name',
          formalDate: 'mocked-formal-date',
          type: 'mocked-type',
          grounds: ['mocked-ground-1', 'mocked-ground-2'],
          size: 1024,
          mimeType: 'mocked/mime-type',
        },
        documentLanguageOptions,
        documentTypeOptions,
        endpoint: 'https://mocked-endpoint',
        groundOptions,
        isEditMode: false,
        uploadGroupId: 'mocked-upload-group-id',
      },
      global: {
        stubs: {
          Form: false,
          Pending: false,
        },
      },
      shallow: true,
    });
  };

  const getComponent = (
    componentName: string,
    createComponentOptions: Partial<CreateComponentOptions> = {},
  ) =>
    createComponent(createComponentOptions).findComponent({
      name: componentName,
    });

  test('should display a file upload field', () => {
    const allowedMimeTypes = ['mocked/mime-type-1', 'mocked/mime-type-2'];
    const component = getComponent('InputDocumentFile', { allowedMimeTypes });
    expect(component.props('allowedMimeTypes')).toEqual(allowedMimeTypes);
    expect(component.props('fileInfo')).toEqual(
      expect.objectContaining({
        name: 'mocked-name',
        size: 1024,
        type: 'mocked/mime-type',
      }),
    );
  });

  test('should allow the user to provide the internal reference of the file', () => {
    const component = getComponent('InputReference');

    expect(component.props('value')).toBe('mocked-internal-reference');
  });

  test('should allow the user to provide the type of this file', () => {
    const documentTypeOptions = ['mocked-type-1', 'mocked-type-2'];
    const component = getComponent('InputDocumentTypes', {
      documentTypeOptions,
    });

    expect(component.props('options')).toEqual(documentTypeOptions);
    expect(component.props('value')).toBe('mocked-type');
  });

  test('should allow the user to provide the language of this file', () => {
    const documentLanguageOptions = ['mocked-language-1', 'mocked-language-2'];
    const component = getComponent('InputDocumentLanguages', {
      documentLanguageOptions,
    });

    expect(component.props('options')).toEqual(documentLanguageOptions);
    expect(component.props('value')).toBe('mocked-language');
  });

  test('should allow the user to provide a date of this document', () => {
    const component = getComponent('InputDocumentDate');

    expect(component.props('value')).toEqual('mocked-formal-date');
  });

  test('should allow the user to provide grounds for this file', () => {
    const groundOptions = ['mocked-ground-1', 'mocked-ground-2'];
    const component = getComponent('InputGrounds', { groundOptions });

    expect(component.props('options')).toEqual(groundOptions);
    expect(component.props('values')).toEqual([
      'mocked-ground-1',
      'mocked-ground-2',
    ]);
  });
});
