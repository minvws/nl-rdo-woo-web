import { SelectOptions } from '@admin-fe/form/interface';
import { createMockedAttachmentType } from '@js/test/file-type';
import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import PublicationFileForm from './PublicationFileForm.vue';
import type { GroundOptions, PublicationFileTypes } from './interface';

describe('The "PublicationFileForm" component', () => {
  interface CreateComponentOptions {
    allowedFileTypes: string[];
    allowedMimeTypes: string[];
    fileTypeOptions: PublicationFileTypes;
    groundOptions: GroundOptions;
    languageOptions: SelectOptions;
  }

  const createComponent = (options: Partial<CreateComponentOptions> = {}) => {
    const {
      allowedFileTypes = [],
      allowedMimeTypes = [],
      fileTypeOptions = [],
      groundOptions = [],
      languageOptions = [],
    } = options;

    return mount(PublicationFileForm, {
      props: {
        allowedFileTypes,
        allowedMimeTypes,
        endpoint: 'https://mocked-endpoint',
        file: {
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
        fileTypeOptions,
        groundOptions,
        isEditMode: false,
        languageOptions,
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
    const component = getComponent('InputFileUpload', { allowedMimeTypes });
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
    const fileTypeOptions = [
      createMockedAttachmentType('mocked-type-1', 'Mocked label 1'),
      createMockedAttachmentType('mocked-type-2', 'Mocked label 2'),
    ];
    const component = getComponent('InputFileTypes', { fileTypeOptions });

    expect(component.props('options')).toEqual(fileTypeOptions);
    expect(component.props('value')).toBe('mocked-type');
  });

  test('should allow the user to provide the language of this file', () => {
    const languageOptions = [
      { value: 'en', label: 'English' },
      { value: 'nl', label: 'Dutch' },
    ];
    const component = getComponent('InputLanguages', { languageOptions });

    expect(component.props('options')).toEqual(languageOptions);
    expect(component.props('value')).toBe('mocked-language');
  });

  test('should allow the user to provide a date of this document', () => {
    const component = getComponent('InputDate');

    expect(component.props('value')).toEqual('mocked-formal-date');
  });

  test('should allow the user to provide grounds for this file', () => {
    const groundOptions = [
      { citation: 'mocked-citation-1', label: 'mocked-label-1' },
      { citation: 'mocked-citation-2', label: 'mocked-label-2' },
    ];
    const component = getComponent('InputGrounds', { groundOptions });

    expect(component.props('options')).toEqual(groundOptions);
    expect(component.props('values')).toEqual([
      'mocked-ground-1',
      'mocked-ground-2',
    ]);
  });
});
