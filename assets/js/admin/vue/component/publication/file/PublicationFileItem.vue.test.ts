import { formatFileSize, getFileTypeByMimeType } from '@js/admin/utils';
import { formatDate } from '@js/utils';
import { createMockedAttachmentType } from '@js/test';
import { flushPromises, mount, VueWrapper } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import PublicationFileItem from './PublicationFileItem.vue';
import type { PublicationFileTypes } from './interface';

describe('the <PublicationFileItem /> component', () => {
  const mockedPublicationFileTypes: PublicationFileTypes = [
    createMockedAttachmentType('mocked-value', 'Mocked label'),
  ];

  interface Options {
    canDelete: boolean;
  }

  const createComponent = (options: Partial<Options> = {}) => {
    const { canDelete = false } = options;
    return mount(PublicationFileItem, {
      props: {
        canDelete,
        id: 'mocked-id',
        date: '2012-01-01',
        endpoint: 'mocked-endpoint',
        fileName: 'mocked-file-name',
        fileSize: 100,
        fileTypes: mockedPublicationFileTypes,
        fileTypeValue: 'mocked-file-type-value',
        mimeType: 'mocked-mime-type',
        withdrawUrl: 'mocked-withdraw-url',
      },
      shallow: true,
      global: {
        renderStubDefaultSlot: true,
        stubs: {
          UploadedFile: false,
        },
      },
    });
  };

  const getUploadedFileComponent = (wrapper: VueWrapper) =>
    wrapper.findComponent({ name: 'UploadedFile' });

  const getAlertComponent = (wrapper: VueWrapper) =>
    wrapper.findComponent({ name: 'Alert' });

  const deleteFile = async (uploadedFileComponent: VueWrapper) => {
    uploadedFileComponent.vm.$emit('delete');
    await flushPromises();
  };

  const getEditButton = (wrapper: VueWrapper) =>
    wrapper.find('button[aria-haspopup="dialog"]');

  beforeEach(() => {
    global.fetch = vi.fn().mockImplementation(() =>
      Promise.resolve({
        status: 200,
      }),
    );

    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.resetAllMocks();
  });

  it('should display an <UploadedFile /> component', () => {
    const component = createComponent();
    const uploadedFileComponent = getUploadedFileComponent(component);

    expect(uploadedFileComponent.props()).toMatchObject({
      canDelete: false,
      fileName: 'mocked-file-name',
      fileSize: 100,
      mimeType: 'mocked-mime-type',
      withdrawUrl: 'mocked-withdraw-url',
    });

    expect(uploadedFileComponent.text()).toContain(
      `Mocked label - ${formatDate('2012-01-01')} (${getFileTypeByMimeType('mocked-mime-type')}, ${formatFileSize(100)})`,
    );
  });

  describe('when the user tries to delete a file', () => {
    it('should make a request to the provided endpoint', async () => {
      const component = createComponent();

      expect(global.fetch).not.toHaveBeenCalled();

      await deleteFile(getUploadedFileComponent(component));

      expect(global.fetch).toHaveBeenNthCalledWith(1, 'mocked-endpoint', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          accept: 'application/json',
        },
      });
    });

    describe('when the request is successful', () => {
      it('should emit a deleted event', async () => {
        const component = createComponent();

        expect(component.emitted('deleted')).toBeUndefined();

        await deleteFile(getUploadedFileComponent(component));

        expect(component.emitted('deleted')).toBeDefined();
      });

      it('should not display an alert saying an error occurred', async () => {
        const component = createComponent();

        await deleteFile(getUploadedFileComponent(component));

        expect(getAlertComponent(component).exists()).toBe(false);
      });
    });

    describe('when the request fails', () => {
      beforeEach(() => {
        global.fetch = vi.fn().mockImplementation(() =>
          Promise.resolve({
            status: 400,
          }),
        );
      });

      it('should display an alert saying an error occurred', async () => {
        const component = createComponent();

        expect(getAlertComponent(component).exists()).toBe(false);

        await deleteFile(getUploadedFileComponent(component));

        expect(getAlertComponent(component).text()).toBe(
          'Het verwijderen van "mocked-file-name" is mislukt. Probeer het later opnieuw.',
        );
      });

      it('should emit a deleted event', async () => {
        const component = createComponent();

        await deleteFile(getUploadedFileComponent(component));

        expect(component.emitted('deleted')).toBeUndefined();
      });
    });
  });

  it('should emit an edit event when the user clicks the edit button', async () => {
    const component = createComponent();

    expect(component.emitted('edit')).toBeUndefined();

    await getEditButton(component).trigger('click');

    expect(component.emitted('edit')).toBeDefined();
  });
});
