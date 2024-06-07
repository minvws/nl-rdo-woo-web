import PublicationDocumentForm from '@admin-fe/component/publication/PublicationDocumentForm.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "PublicationDocumentForm" component', () => {
  const createComponent = () => mount(PublicationDocumentForm, {
    props: {
      document: {
        formalDate: 'mocked-formal-date',
        grounds: [],
        internalReference: '',
        language: 'nl',
        name: '',
        type: '',
      },
      documentLanguageOptions: [],
      documentTypeOptions: [],
      endpoint: '',
      groundOptions: [],
    },
    shallow: true,
  });

  const getComponent = (componentName: string) => createComponent().findComponent({ name: componentName });

  test('should display a file upload field', () => {
    expect(getComponent('InputDocumentFile')).toBeTruthy();
  });

  test('should allow the user to provide the file name', () => {
    expect(getComponent('InputDocumentName')).toBeTruthy();
  });

  test('should allow the user to provide the internal reference of the file', () => {
    expect(getComponent('InputReference')).toBeTruthy();
  });

  test('should allow the user to provide the type of this file', () => {
    expect(getComponent('InputDocumentTypes')).toBeTruthy();
  });

  test('should allow the user to provide the language of this file', () => {
    expect(getComponent('InputDocumentLanguages')).toBeTruthy();
  });

  test('should allow the user to provide grounds for this file', () => {
    expect(getComponent('InputGrounds')).toBeTruthy();
  });
});
