import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import WooDecisionAddDocumentsController from './WooDecisionAddDocumentsController.vue';

describe('The "<WooDecisionAddDocumentsController />" component', () => {
  const createComponent = () =>
    mount(WooDecisionAddDocumentsController, {
      props: {
        allowedFileTypes: ['pdf'],
        allowedMimeTypes: ['application/pdf'],
        dossierId: 'mocked-dossier-id',
        isComplete: false,
        maxFileSize: 1000000,
        processEndpoint: 'mocked-process-endpoint',
        statusEndpoint: 'mocked-status-endpoint',
        uploadEndpoint: 'mocked-upload-endpoint',
        nextStepUrl: '#mocked-next-step-url',
        continueLaterUrl: '#mocked-continue-later-url',
        confirmEndpoint: 'mocked-confirm-endpoint',
        rejectEndpoint: 'mocked-reject-endpoint',
      },
      shallow: true,
    });

  const getComponent = () =>
    createComponent().findComponent({ name: 'WooDecisionAddDocuments' });

  test('should render a <WooDecisionAddDocuments /> component', async () => {
    expect(getComponent().props()).toEqual({
      allowedFileTypes: ['pdf'],
      allowedMimeTypes: ['application/pdf'],
      dossierId: 'mocked-dossier-id',
      isComplete: false,
      maxFileSize: 1000000,
      processEndpoint: 'mocked-process-endpoint',
      statusEndpoint: 'mocked-status-endpoint',
      uploadEndpoint: 'mocked-upload-endpoint',
      nextStepUrl: '#mocked-next-step-url',
      continueLaterUrl: '#mocked-continue-later-url',
      confirmEndpoint: 'mocked-confirm-endpoint',
      rejectEndpoint: 'mocked-reject-endpoint',
    });
  });
});
