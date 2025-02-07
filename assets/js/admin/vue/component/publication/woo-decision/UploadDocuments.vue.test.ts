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

describe('The "UploadDocuments" component', () => {
  const getUploadAreaComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'UploadArea' });

  interface Options {
    isComplete: boolean;
  }

  const createComponent = (options: Partial<Options> = {}) => {
    const { isComplete = false } = options;
    return mount(UploadDocuments, {
      props: {
        allowedFileTypes: ['pdf'],
        allowedMimeTypes: ['application/pdf'],
        dossierId: 'mocked-dossier-id',
        isComplete,
        maxFileSize: 1000000,
        processEndpoint: 'mocked-process-endpoint',
        statusEndpoint: 'mocked-status-endpoint',
        uploadEndpoint: 'mocked-upload-endpoint',
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

  const getAlertComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'Alert' });

  const getErrorMessagesComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'ErrorMessages' });

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

  beforeEach(() => {
    vi.useFakeTimers();

    type StatusResponse = z.TypeOf<
      typeof wooDecisionUploadStatusResponseSchema
    >;

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

    global.fetch = vi
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
          canProcess: true,
          currentDocumentsCount: 2,
          uploadedFiles: [
            { id: '3', name: '3.pdf', mimeType: 'application/pdf' },
          ],
          missingDocuments: ['3.pdf'],
        }),
      );
  });

  afterEach(() => {
    vi.clearAllMocks();
    vi.useRealTimers();
  });

  describe('the <UploadArea /> component', () => {
    test('should be visible when the upload status is open for uploads', () => {
      expect(getUploadAreaComponent(createComponent()).props()).toMatchObject({
        allowedFileTypes: ['pdf'],
        allowedMimeTypes: ['application/pdf'],
        allowMultiple: true,
        enableAutoUpload: true,
        endpoint: 'mocked-upload-endpoint',
        id: 'upload-area-dossier-files',
        maxFileSize: 1000000,
        payload: {
          dossierId: 'mocked-dossier-id',
          groupId: 'woo-decision-documents',
        },
        tip: 'Tip: je kunt meerdere documenten tegelijkertijd uploaden. Sleep je hele selectie (of een zip-bestand) naar dit venster.',
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
      expect(global.fetch).toHaveBeenNthCalledWith(1, 'mocked-status-endpoint');
    });

    test('should not occur when the uploads are already complete', () => {
      createComponent({ isComplete: true });
      expect(global.fetch).not.toHaveBeenCalled();
    });

    test('should stop when the status returns that all documents are uploaded', async () => {
      createComponent();

      await waitForNumberOfStatusResponses(15);

      expect(global.fetch).toHaveBeenCalledTimes(5);
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
      expect(global.fetch).not.toHaveBeenCalledWith('mocked-process-endpoint', {
        method: 'POST',
      });

      await waitForNumberOfStatusResponses(2);
      await clickProcessButton(component);

      expect(global.fetch).toHaveBeenCalledWith('mocked-process-endpoint', {
        method: 'POST',
      });
    });
  });

  test('should display a list of missing documents', async () => {
    const component = createComponent();

    expect(component.text()).not.toContain('Nog te uploaden:');

    await waitForNumberOfStatusResponses(2);

    expect(component.text()).toContain('Nog te uploaden:');
  });

  test('should display a message saying that files are being processed when they are', async () => {
    const component = createComponent();

    expect(getAlertComponent(component).exists()).toBe(false);

    await waitForNumberOfStatusResponses(3);

    expect(getAlertComponent(component).exists()).toBe(true);
  });
});
