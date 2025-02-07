import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import AddDocuments from './AddDocuments.vue';

describe('The "AddDocuments" component', () => {
  const getUploadDocumentsComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'UploadDocuments' });

  const getAlertMessageComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'Alert' });

  const getErrorMessagesComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'ErrorMessages' });

  const getNextStepLink = (component: VueWrapper) =>
    component.findAll('a').at(0);

  const getContinueLaterLink = (component: VueWrapper) =>
    component.findAll('a').at(1);

  const clickNextStepLink = async (component: VueWrapper) => {
    await getNextStepLink(component)?.trigger('click');
  };

  const makeUploadComplete = async (component: VueWrapper) => {
    await getUploadDocumentsComponent(component).vm.$emit('on-complete');
  };

  const createComponent = () =>
    mount(AddDocuments, {
      props: {
        allowedFileTypes: ['pdf'],
        allowedMimeTypes: ['application/pdf'],
        dossierId: 'mocked-dossier-id',
        expectedUploadCount: 1,
        isComplete: false,
        maxFileSize: 1000000,
        processEndpoint: 'https://mocked-process-endpoint.mock',
        statusEndpoint: 'https://mocked-status-endpoint.mock',
        uploadEndpoint: 'https://mocked-upload-endpoint.mock',
        nextStepUrl: '#mocked-next-step-url',
        continueLaterUrl: '#mocked-continue-later-url',
      },
      shallow: true,
      global: {
        renderStubDefaultSlot: true,
      },
    });

  test('should render the "UploadDocuments" component', () => {
    expect(getUploadDocumentsComponent(createComponent()).props()).toEqual({
      allowedFileTypes: ['pdf'],
      allowedMimeTypes: ['application/pdf'],
      dossierId: 'mocked-dossier-id',
      isComplete: false,
      maxFileSize: 1000000,
      processEndpoint: 'https://mocked-process-endpoint.mock',
      statusEndpoint: 'https://mocked-status-endpoint.mock',
      uploadEndpoint: 'https://mocked-upload-endpoint.mock',
    });
  });

  describe('the "next step" link', () => {
    test('should be displayed', () => {
      const component = createComponent();

      expect(getNextStepLink(component)?.attributes('href')).toBe(
        '#mocked-next-step-url',
      );
    });

    test('should display an error message when clicking it while the upload is not complete yet', async () => {
      const component = createComponent();

      expect(getErrorMessagesComponent(component).exists()).toBe(false);

      await clickNextStepLink(component);

      expect(getErrorMessagesComponent(component).exists()).toBe(true);
    });
  });

  test('should render a link which allows the user to continue later', () => {
    const component = createComponent();

    expect(getContinueLaterLink(component)?.attributes('href')).toBe(
      '#mocked-continue-later-url',
    );
  });

  test('should hide the error message again when it is visible and we are being notified that the upload is complete', async () => {
    const component = createComponent();

    await clickNextStepLink(component);
    expect(getErrorMessagesComponent(component).exists()).toBe(true);

    await makeUploadComplete(component);
    await clickNextStepLink(component);
    expect(getErrorMessagesComponent(component).exists()).toBe(false);
  });

  test('should display a message saying all files are uploaded when the upload is complete', async () => {
    const component = createComponent();

    expect(getAlertMessageComponent(component).exists()).toBe(false);

    await makeUploadComplete(component);

    expect(getAlertMessageComponent(component).text()).toContain(
      'Alle documenten uit het productierapport zijn geüpload.',
    );
  });
});
