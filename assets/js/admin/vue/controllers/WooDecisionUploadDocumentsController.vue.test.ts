import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import WooDecisionUploadDocumentsController from './WooDecisionUploadDocumentsController.vue';

describe('The "<WooDecisionUploadDocumentsController />" component', () => {
  const createComponent = () =>
    mount(WooDecisionUploadDocumentsController, {
      props: {
        allowedFileTypes: ['pdf'],
        allowedMimeTypes: ['application/pdf'],
        dossierId: 'mocked-dossier-id',
        isComplete: false,
        maxFileSize: 1000000,
        confirmEndpoint: 'mocked-confirm-endpoint',
        rejectEndpoint: 'mocked-reject-endpoint',
        processEndpoint: 'mocked-process-endpoint',
        statusEndpoint: 'mocked-status-endpoint',
        uploadEndpoint: 'mocked-upload-endpoint',
      },
      shallow: true,
    });

  const getComponent = () =>
    createComponent().findComponent({ name: 'WooDecisionUploadDocuments' });

  test('should render a <WooDecisionUploadDocuments /> component', async () => {
    expect(getComponent().props()).toEqual({
      allowedFileTypes: ['pdf'],
      allowedMimeTypes: ['application/pdf'],
      dossierId: 'mocked-dossier-id',
      isComplete: false,
      maxFileSize: 1000000,
      mode: 'replace',
      confirmEndpoint: 'mocked-confirm-endpoint',
      rejectEndpoint: 'mocked-reject-endpoint',
      processEndpoint: 'mocked-process-endpoint',
      statusEndpoint: 'mocked-status-endpoint',
      uploadEndpoint: 'mocked-upload-endpoint',
    });
  });
});
