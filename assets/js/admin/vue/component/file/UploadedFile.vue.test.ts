import { mount, VueWrapper } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import UploadedFile from './UploadedFile.vue';
import { formatFileSize } from '@js/admin/utils/file';

describe('The <UploadedFile /> component', () => {
  interface Options {
    canDelete: boolean;
    fileSize: number;
    hasExtraSlot: boolean;
    withdrawUrl: string;
  }

  const createComponent = (options: Partial<Options> = {}) => {
    const {
      canDelete = true,
      fileSize,
      hasExtraSlot = false,
      withdrawUrl,
    } = options;
    return mount(UploadedFile, {
      props: {
        canDelete,
        fileName: 'mocked-file-name',
        fileSize,
        mimeType: 'mocked-mime-type',
        withdrawUrl,
      },
      slots: hasExtraSlot ? { extra: '<div>mocked-extra-slot</div>' } : {},
      shallow: true,
    });
  };

  const getMimeTypeIconComponent = (wrapper: VueWrapper) =>
    wrapper.findComponent({ name: 'MimeTypeIcon' });

  const getDeleteButton = (wrapper: VueWrapper) =>
    wrapper.find('button[type="button"]');

  const getWithdrawLink = (wrapper: VueWrapper) => wrapper.find('a');

  it('should display the icon type', () => {
    expect(getMimeTypeIconComponent(createComponent()).props('mimeType')).toBe(
      'mocked-mime-type',
    );
  });

  it('should display the file name', () => {
    expect(createComponent().text()).toContain('mocked-file-name');
  });

  it('should display the file size', () => {
    expect(createComponent({ fileSize: 100 }).text()).toContain(
      formatFileSize(100),
    );
  });

  it('should display the extra slot', () => {
    expect(createComponent({ hasExtraSlot: true }).text()).toContain(
      'mocked-extra-slot',
    );
  });

  describe('when the file can be deleted', () => {
    it('should display the delete button', () => {
      expect(getDeleteButton(createComponent()).exists()).toBe(true);
    });

    it('should emit a delete event when pressing the delete button', async () => {
      const component = createComponent();

      expect(component.emitted('delete')).toBeUndefined();

      await getDeleteButton(component).trigger('click');

      expect(component.emitted('delete')).toBeDefined();
    });

    it('should not display the withdraw link', () => {
      expect(getWithdrawLink(createComponent()).exists()).toBe(false);
    });
  });

  describe('when the file can not be deleted', () => {
    it('should not display the delete button', () => {
      expect(
        getDeleteButton(createComponent({ canDelete: false })).exists(),
      ).toBe(false);
    });

    it('should display the withdraw link when a withdraw url is provided', async () => {
      const component = createComponent({
        canDelete: false,
        withdrawUrl: 'mocked-withdraw-url',
      });

      expect(getWithdrawLink(component).attributes('href')).toBe(
        'mocked-withdraw-url',
      );

      await component.setProps({ withdrawUrl: undefined });

      expect(getWithdrawLink(component).exists()).toBe(false);
    });
  });
});
