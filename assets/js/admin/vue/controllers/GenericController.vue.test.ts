import { mount, type VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import GenericController from './GenericController.vue';

describe('The <GenericController /> component', () => {
  interface Options {
    componentName: string;
    [key: string]: unknown;
  }

  const createComponent = (options: Options) => {
    const { componentName, ...props } = options;

    return mount(GenericController, {
      props: {
        componentName,
        ...props,
      },
      shallow: true,
    });
  };

  const findComponent = (componentName: string, wrapper: VueWrapper) =>
    wrapper.findComponent({
      name: componentName,
    });

  test('should render nothing when the property "componentName" is invalid', () => {
    const component = createComponent({
      componentName: 'InvalidComponentName',
    });

    expect(component.html()).toBe('');
  });

  test('should render the <PublicationFiles /> component when the property "componentName" equals "PublicationFiles"', () => {
    const component = createComponent({
      componentName: 'PublicationFiles',

      canDelete: true,
      endpoint: 'mocked-endpoint',
      fileLimits: [
        {
          mimeTypes: ['application/pdf'],
          label: 'PDF',
        },
      ],
      fileTypeOptions: [],
      groundOptions: [],
      languageOptions: [],
      uploadGroupId: 'mocked-upload-group-id',
    });

    expect(findComponent('PublicationFiles', component).props()).toMatchObject({
      canDelete: true,
      endpoint: 'mocked-endpoint',
      fileLimits: [
        {
          mimeTypes: ['application/pdf'],
          label: 'PDF',
        },
      ],
      fileTypeOptions: [],
      groundOptions: [],
      languageOptions: [],
    });
  });

  test('should render the <LinkDossiers /> component when the property "componentName" equals "LinkDossiers"', () => {
    const component = createComponent({
      componentName: 'LinkDossiers',

      name: 'mocked-name',
      submitErrors: ['mocked-submit-error-1', 'mocked-submit-error-2'],
    });

    expect(findComponent('LinkDossiers', component).props()).toMatchObject({
      name: 'mocked-name',
      submitErrors: ['mocked-submit-error-1', 'mocked-submit-error-2'],
    });
  });

  test('should render the <MultiCombobox /> component when the property "componentName" equals "MultiCombobox"', () => {
    const component = createComponent({
      componentName: 'MultiCombobox',

      buttonText: 'mocked-button-text',
      label: 'mocked-label',
      legend: 'mocked-legend',
      name: 'mocked-name',
      options: [{ label: 'mocked-option-label', value: 'mocked-option-value' }],
      values: ['mocked-value-1', 'mocked-value-2'],
    });

    expect(findComponent('MultiCombobox', component).props()).toMatchObject({
      buttonText: 'mocked-button-text',
      label: 'mocked-label',
      legend: 'mocked-legend',
      name: 'mocked-name',
      options: [{ label: 'mocked-option-label', value: 'mocked-option-value' }],
      values: ['mocked-value-1', 'mocked-value-2'],
    });
  });

  test('should render the <MultiSelect /> component when the property "componentName" equals "MultiSelect"', () => {
    const component = createComponent({
      componentName: 'MultiSelect',

      buttonText: 'mocked-button-text',
      label: 'mocked-label',
      legend: 'mocked-legend',
      name: 'mocked-name',
      options: [{ label: 'mocked-option-label', value: 'mocked-option-value' }],
      values: ['mocked-value-1', 'mocked-value-2'],
    });

    expect(findComponent('MultiSelect', component).props()).toMatchObject({
      buttonText: 'mocked-button-text',
      label: 'mocked-label',
      legend: 'mocked-legend',
      name: 'mocked-name',
      options: [{ label: 'mocked-option-label', value: 'mocked-option-value' }],
      values: ['mocked-value-1', 'mocked-value-2'],
    });
  });

  test('should render the <PublicationSearchAndGo /> component when the property "componentName" equals "PublicationSearchAndGo"', () => {
    const component = createComponent({
      componentName: 'PublicationSearchAndGo',

      dossierId: 'mocked-dossier-id',
      label: 'mocked-label',
      resultType: 'mocked-result-type',
    });

    expect(
      findComponent('PublicationSearchAndGo', component).props(),
    ).toMatchObject({
      dossierId: 'mocked-dossier-id',
      label: 'mocked-label',
      resultType: 'mocked-result-type',
    });
  });

  test('should render the <UploadArea /> component when the property "componentName" equals "UploadArea"', () => {
    const component = createComponent({
      componentName: 'UploadArea',

      endpoint: 'mocked-endpoint',
      fileLimits: [
        {
          mimeTypes: [
            'mocked-allowed-mime-type-1',
            'mocked-allowed-mime-type-2',
          ],
          label: 'mocked-label',
        },
      ],
      name: 'mocked-name',
      payload: {
        mocked: 'payload',
      },
      tip: 'mocked-tip',
    });

    expect(findComponent('UploadArea', component).props()).toMatchObject({
      endpoint: 'mocked-endpoint',
      fileLimits: [
        {
          mimeTypes: [
            'mocked-allowed-mime-type-1',
            'mocked-allowed-mime-type-2',
          ],
          label: 'mocked-label',
        },
      ],
      name: 'mocked-name',
      payload: {
        mocked: 'payload',
      },
      tip: 'mocked-tip',
    });
  });

  test('should render the <WooDecisionAddDocuments /> component when the property "componentName" equals "WooDecisionAddDocuments"', () => {
    const component = createComponent({
      componentName: 'WooDecisionAddDocuments',

      dossierId: 'mocked-dossier-id',
      fileLimits: [
        {
          mimeTypes: [
            'mocked-allowed-mime-type-1',
            'mocked-allowed-mime-type-2',
          ],
          label: 'mocked-label',
        },
      ],
      isComplete: false,
      maxFileSize: 1000,
      confirmEndpoint: 'mocked-confirm-endpoint',
      rejectEndpoint: 'mocked-reject-endpoint',
      processEndpoint: 'mocked-process-endpoint',
      statusEndpoint: 'mocked-status-endpoint',
      uploadEndpoint: 'mocked-upload-endpoint',
      nextStepUrl: 'mocked-next-step-url',
      continueLaterUrl: 'mocked-continue-later-url',
    });

    expect(findComponent('AddDocuments', component).props()).toMatchObject({
      dossierId: 'mocked-dossier-id',
      fileLimits: [
        {
          mimeTypes: [
            'mocked-allowed-mime-type-1',
            'mocked-allowed-mime-type-2',
          ],
          label: 'mocked-label',
        },
      ],
      confirmEndpoint: 'mocked-confirm-endpoint',
      rejectEndpoint: 'mocked-reject-endpoint',
      processEndpoint: 'mocked-process-endpoint',
      statusEndpoint: 'mocked-status-endpoint',
      uploadEndpoint: 'mocked-upload-endpoint',
      nextStepUrl: 'mocked-next-step-url',
      continueLaterUrl: 'mocked-continue-later-url',
    });
  });

  test('should render the <WooDecisionUploadDocuments /> component when the property "componentName" equals "WooDecisionUploadDocuments"', () => {
    const component = createComponent({
      componentName: 'WooDecisionUploadDocuments',

      dossierId: 'mocked-dossier-id',
      fileLimits: [
        {
          mimeTypes: [
            'mocked-allowed-mime-type-1',
            'mocked-allowed-mime-type-2',
          ],
          label: 'mocked-label',
          size: 1000,
        },
      ],
      isComplete: false,
      mode: 'replace',
      confirmEndpoint: 'mocked-confirm-endpoint',
      rejectEndpoint: 'mocked-reject-endpoint',
      processEndpoint: 'mocked-process-endpoint',
      statusEndpoint: 'mocked-status-endpoint',
      uploadEndpoint: 'mocked-upload-endpoint',
    });

    expect(findComponent('UploadDocuments', component).props()).toMatchObject({
      dossierId: 'mocked-dossier-id',
      fileLimits: [
        {
          mimeTypes: [
            'mocked-allowed-mime-type-1',
            'mocked-allowed-mime-type-2',
          ],
          label: 'mocked-label',
          size: 1000,
        },
      ],
      mode: 'replace',
      confirmEndpoint: 'mocked-confirm-endpoint',
      rejectEndpoint: 'mocked-reject-endpoint',
      processEndpoint: 'mocked-process-endpoint',
      statusEndpoint: 'mocked-status-endpoint',
      uploadEndpoint: 'mocked-upload-endpoint',
    });
  });

  test('should render the <MultiText /> component when the property "componentName" equals "MultiText"', () => {
    const component = createComponent({
      componentName: 'MultiText',

      buttonText: 'mocked-button-text',
      buttonTextMultiple: 'mocked-button-text-multiple',
      helpText: 'mocked-help-text',
      immutableValues: ['mocked-immutable-value-1', 'mocked-immutable-value-2'],
      label: 'mocked-label',
      legend: 'mocked-legend',
      minLength: 100,
      maxLength: 100,
      name: 'mocked-name',
      submitErrors: ['mocked-submit-error-1', 'mocked-submit-error-2'],
      values: ['mocked-value-1', 'mocked-value-2'],
    });

    expect(findComponent('MultiText', component).props()).toMatchObject({
      buttonText: 'mocked-button-text',
      buttonTextMultiple: 'mocked-button-text-multiple',
      helpText: 'mocked-help-text',
      immutableValues: ['mocked-immutable-value-1', 'mocked-immutable-value-2'],
      label: 'mocked-label',
      legend: 'mocked-legend',
      minLength: 100,
      maxLength: 100,
      name: 'mocked-name',
      submitErrors: ['mocked-submit-error-1', 'mocked-submit-error-2'],
      values: ['mocked-value-1', 'mocked-value-2'],
    });
  });

  test('should render the <MarkdownEditor /> component when the property "componentName" equals "MarkdownEditor"', () => {
    const component = createComponent({
      componentName: 'MarkdownEditor',

      id: 'mocked-id',
      name: 'mocked-name',
      value: 'mocked-value',
    });

    expect(findComponent('MarkdownEditor', component).props()).toMatchObject({
      id: 'mocked-id',
      name: 'mocked-name',
      value: 'mocked-value',
    });
  });

  test('should render the <DepartmentLogoManager /> component when the property "componentName" equals "DepartmentLogoManager"', () => {
    const component = createComponent({
      componentName: 'DepartmentLogoManager',

      deleteEndpoint: 'mocked-delete-endpoint',
      departmentId: 'mocked-department-id',
      hasLogo: true,
      logoEndpoint: 'mocked-logo-endpoint',
      uploadEndpoint: 'mocked-upload-endpoint',
    });

    expect(
      findComponent('DepartmentLogoManager', component).props(),
    ).toMatchObject({
      deleteEndpoint: 'mocked-delete-endpoint',
      departmentId: 'mocked-department-id',
      hasLogo: true,
      logoEndpoint: 'mocked-logo-endpoint',
      uploadEndpoint: 'mocked-upload-endpoint',
    });
  });
});
