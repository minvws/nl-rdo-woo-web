import { flushPromises, mount, VueWrapper } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import UploadDocuments from './UploadDocuments.vue';
import {
  UploadStatus,
  wooDecisionUploadStatusResponseSchema,
} from './interface';
import { z } from 'zod';
import { nextTick } from 'vue';

vi.mock('@js/admin/utils');

describe('The "<UploadDocuments />" component', () => {
  const getUploadAreaComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'UploadArea' });

  interface Options {
    isComplete: boolean;
    mode: 'add' | 'replace';
  }

  const createComponent = (options: Partial<Options> = {}) => {
    const { isComplete = false, mode = 'add' } = options;
    return mount(UploadDocuments, {
      props: {
        dossierId: 'mocked-dossier-id',
        fileLimits: [
          { size: 1024 * 2, mimeTypes: ['application/pdf'], label: 'PDF' },
        ],
        isComplete,
        mode,
        confirmEndpoint: 'mocked-confirm-endpoint',
        rejectEndpoint: 'mocked-reject-endpoint',
        processEndpoint: 'mocked-process-endpoint',
        statusEndpoint: 'mocked-status-endpoint',
        uploadEndpoint: 'mocked-upload-endpoint',
        maxCombinedFileSize: 123456789,
      },
      shallow: true,
      global: {
        renderStubDefaultSlot: true,
      },
    });
  };

  const getProcessButtonElement = (component: VueWrapper) =>
    component.find('button');

  const clickProcessButton = async (component: VueWrapper) => {
    await getProcessButtonElement(component).trigger('click');
  };

  const getErrorMessagesComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'ErrorMessages' });

  const getIsProcessingComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'IsProcessingDocuments' });

  const getIsCheckingComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'IsCheckingDocuments' });

  const getMissingDocumentsComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'MissingDocuments' });

  const getMaxCombinedSizeExceededComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'MaxCombinedSizeExceeded' });

  const waitForNumberOfStatusResponses = async (numberOfResponses: number) => {
    for (let i = 0; i < numberOfResponses; i++) {
      await waitForNextStatusResponse();
    }
  };

  const waitForNextStatusResponse = async () => {
    await flushPromises();
    vi.advanceTimersByTime(2500 + 1);
    await nextTick();
  };

  type StatusResponse = z.TypeOf<typeof wooDecisionUploadStatusResponseSchema>;

  const createStatusResponse = (properties: Partial<StatusResponse> = {}) =>
    Promise.resolve({
      json: () =>
        Promise.resolve({
          canProcess: false,
          currentDocumentsCount: 3,
          dossierId: '123',
          expectedDocumentsCount: 3,
          missingDocuments: [],
          status: UploadStatus.OpenForUploads,
          uploadedFiles: [],
          ...properties,
        }),
    });

  beforeEach(() => {
    vi.useFakeTimers();

    window.fetch = vi
      .fn()
      .mockImplementation(() => createStatusResponse())
      .mockImplementationOnce(() =>
        createStatusResponse({
          currentDocumentsCount: 0,
          missingDocuments: ['1.pdf', '2.pdf', '3.pdf'],
        }),
      )
      .mockImplementationOnce(() =>
        createStatusResponse({
          canProcess: true,
          currentDocumentsCount: 0,
          uploadedFiles: [
            { id: '1', name: '1.pdf', mimeType: 'application/pdf' },
            { id: '2', name: '2.pdf', mimeType: 'application/pdf' },
          ],
          missingDocuments: ['1.pdf', '2.pdf', '3.pdf'],
        }),
      )
      .mockImplementationOnce(() =>
        createStatusResponse({
          canProcess: false,
          currentDocumentsCount: 0,
          uploadedFiles: [],
          missingDocuments: ['1.pdf', '2.pdf', '3.pdf'],
          status: UploadStatus.ProcessingUploads,
        }),
      )
      .mockImplementationOnce(() =>
        createStatusResponse({
          canProcess: false,
          currentDocumentsCount: 0,
          uploadedFiles: [],
          missingDocuments: ['1.pdf', '2.pdf', '3.pdf'],
          status: UploadStatus.ProcessingUpdates,
        }),
      )
      .mockImplementationOnce(() =>
        createStatusResponse({
          canProcess: true,
          currentDocumentsCount: 2,
          uploadedFiles: [
            { id: '3', name: '3.pdf', mimeType: 'application/pdf' },
          ],
          missingDocuments: ['3.pdf'],
        }),
      )
      .mockImplementationOnce(() =>
        createStatusResponse({
          canProcess: false,
          currentDocumentsCount: 3,
          uploadedFiles: [
            { id: '1', name: '1.pdf', mimeType: 'application/pdf' },
            { id: '2', name: '2.pdf', mimeType: 'application/pdf' },
            { id: '3', name: '3.pdf', mimeType: 'application/pdf' },
          ],
          missingDocuments: [],
          status: UploadStatus.Completed,
        }),
      );
  });

  afterEach(() => {
    vi.clearAllMocks();
    vi.useRealTimers();
  });

  describe('the <UploadArea /> component', () => {
    test('should be visible when the upload status is open for uploads', () => {
      expect(
        getUploadAreaComponent(createComponent({ mode: 'replace' })).props(),
      ).toMatchObject({
        allowMultiple: true,
        enableAutoUpload: true,
        endpoint: 'mocked-upload-endpoint',
        fileLimits: [
          { size: 1024 * 2, mimeTypes: ['application/pdf'], label: 'PDF' },
        ],
        id: 'upload-area-dossier-files',
        payload: {
          dossierId: 'mocked-dossier-id',
          groupId: 'woo-decision-documents',
        },
      });
    });

    test('should be hidden if the uploads are already complete', () => {
      expect(
        getUploadAreaComponent(createComponent({ isComplete: true })).exists(),
      ).toBe(false);
    });

    test('should be hidden if the backend is currently processing', async () => {
      const component = createComponent();

      expect(getUploadAreaComponent(component).exists()).toBe(true);

      await waitForNumberOfStatusResponses(3);

      expect(getUploadAreaComponent(component).exists()).toBe(false);
    });
  });

  describe('checking the status', () => {
    test('should be done by making a request to the status endpoint', () => {
      createComponent();
      expect(window.fetch).toHaveBeenNthCalledWith(1, 'mocked-status-endpoint');
    });

    test('should not occur when the uploads are already complete', () => {
      createComponent({ isComplete: true });
      expect(window.fetch).not.toHaveBeenCalled();
    });

    test('should stop when the status returns that all documents are uploaded', async () => {
      createComponent();

      await waitForNumberOfStatusResponses(15);

      expect(window.fetch).toHaveBeenCalledTimes(6);
    });
  });

  describe('the process button', () => {
    test('should display an error message when pressing it while files can not be processed', async () => {
      const component = createComponent();

      expect(getErrorMessagesComponent(component).exists()).toBe(false);

      await clickProcessButton(component);

      expect(getErrorMessagesComponent(component).exists()).toBe(true);
    });

    test('should make a request to process files when pressing it while files can be processed', async () => {
      const component = createComponent();

      await clickProcessButton(component);
      expect(window.fetch).not.toHaveBeenCalledWith('mocked-process-endpoint', {
        method: 'POST',
      });

      await waitForNumberOfStatusResponses(2);
      await clickProcessButton(component);

      expect(window.fetch).toHaveBeenCalledWith('mocked-process-endpoint', {
        method: 'POST',
      });
    });
  });

  test('should display a list of missing documents', async () => {
    const component = createComponent();

    expect(getMissingDocumentsComponent(component).exists()).toBe(false);

    await waitForNumberOfStatusResponses(2);

    expect(getMissingDocumentsComponent(component).exists()).toBe(true);
  });

  test('should display a message saying that documents are being checked when they are', async () => {
    const component = createComponent();

    expect(getIsCheckingComponent(component).exists()).toBe(false);

    await waitForNumberOfStatusResponses(3);

    expect(getIsCheckingComponent(component).exists()).toBe(true);
  });

  test('should display a message saying that documents are being processed when they are', async () => {
    const component = createComponent();

    expect(getIsProcessingComponent(component).exists()).toBe(false);

    await waitForNumberOfStatusResponses(4);

    expect(getIsProcessingComponent(component).exists()).toBe(true);

    await waitForNumberOfStatusResponses(1);

    expect(getIsProcessingComponent(component).exists()).toBe(false);
  });

  describe('the max combined size exceeded component', () => {
    beforeEach(() => {
      window.fetch = vi.fn().mockImplementation(() =>
        createStatusResponse({
          status: UploadStatus.MaxSizeExceeded,
        }),
      );
    });

    test('should not be displayed by default', async () => {
      const component = createComponent();

      expect(getMaxCombinedSizeExceededComponent(component).exists()).toBe(
        false,
      );

      await waitForNumberOfStatusResponses(1);

      const maxCombinedSizeExceededComponent =
        getMaxCombinedSizeExceededComponent(component);
      expect(maxCombinedSizeExceededComponent.exists()).toBe(true);
      expect(maxCombinedSizeExceededComponent.props()).toMatchObject({
        maxSize: 123456789,
        rejectEndpoint: 'mocked-reject-endpoint',
      });
    });
  });
});
