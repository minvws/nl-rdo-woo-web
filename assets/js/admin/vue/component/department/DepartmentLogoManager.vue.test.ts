import { createTestFile } from '@js/test';
import { VueWrapper, mount } from '@vue/test-utils';
import { MockInstance, beforeEach, describe, expect, test, vi } from 'vitest';
import DepartmentLogoManager from './DepartmentLogoManager.vue';

describe('The "DepartmentLogoManager" component', () => {
  let fetchSpy: MockInstance;

  type createComponentOptions = {
    hasLogo?: boolean;
  };

  const createComponent = (options: createComponentOptions = {}) => {
    const { hasLogo = false } = options;

    return mount(DepartmentLogoManager, {
      props: {
        deleteEndpoint: 'mocked-delete-endpoint',
        logoEndpoint: 'mocked-logo-endpoint',
        uploadEndpoint: 'mocked-upload-endpoint',
        departmentId: 'department-id',
        hasLogo: hasLogo,
      },
      shallow: true,
      global: {
        renderStubDefaultSlot: true,
      },
    });
  };

  const getUploadAreaComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'UploadArea' });

  const getLabelElement = (component: VueWrapper) => component.find('label');

  const getRemoveButtonElement = (component: VueWrapper) =>
    component.find('button');

  const getImageElement = (component: VueWrapper) => component.find('img');

  const hasLogoRemovedMessage = (component: VueWrapper) =>
    hasAlertWithText(component, 'Logo verwijderd');

  const hasLogoSavedMessage = (component: VueWrapper) =>
    hasAlertWithText(component, 'Logo opgeslagen');

  const hasAlertWithText = (component: VueWrapper, text: string) => {
    try {
      return component.findComponent({ name: 'Alert' })?.text().includes(text);
    } catch {
      return false;
    }
  };

  const mockFileIsUploaded = async (component: VueWrapper) => {
    await getUploadAreaComponent(component).vm.$emit(
      'uploaded',
      createTestFile({ name: 'test.svg' }),
      'mocked-upload-id',
    );
  };

  beforeEach(() => {
    fetchSpy = vi.spyOn(window, 'fetch');

    fetchSpy.mockResolvedValue({
      ok: false,
    });
  });

  describe('when there is no logo', () => {
    test('should display a label and an upload area', () => {
      const component = createComponent();

      expect(getLabelElement(component).attributes('for')).toBe('file');
      expect(getUploadAreaComponent(component).props()).toMatchObject({
        allowedFileTypes: ['SVG'],
        allowedMimeTypes: ['image/svg+xml'],
        enableAutoUpload: true,
        endpoint: 'mocked-upload-endpoint',
        id: 'file',
        maxFileSize: 1024 * 1024 * 10,
        name: 'file',
        payload: { groupId: 'department' },
      });
    });

    test('should not display the logo and the remove button', async () => {
      const component = createComponent();

      expect(getImageElement(component).exists()).toBe(false);
      expect(getRemoveButtonElement(component).exists()).toBe(false);
    });
  });

  describe('when there is a logo', () => {
    test('should display the logo and a button to remove it', () => {
      const component = createComponent({ hasLogo: true });

      expect(getImageElement(component).attributes('src')).toBe(
        'mocked-logo-endpoint',
      );
      expect(getRemoveButtonElement(component).exists()).toBe(true);
    });

    test('should not display the label and the upload area', () => {
      const component = createComponent({ hasLogo: true });

      expect(getLabelElement(component).exists()).toBe(false);
      expect(getUploadAreaComponent(component).exists()).toBe(false);
    });
  });

  describe('when a file is uploaded', () => {
    test('should display the logo which path can be found in the "logoEndpoint" property', async () => {
      vi.spyOn(Date, 'now').mockReturnValue(1234567890);

      const component = createComponent();

      await mockFileIsUploaded(component);
      expect(getImageElement(component).attributes('src')).toBe(
        '/mocked-logo-endpoint?cacheKey=1234567890',
      );
    });
  });

  describe('the message saying the logo was saved', () => {
    test('should be displayed when a file is uploaded', async () => {
      const component = createComponent();

      expect(hasLogoSavedMessage(component)).toBe(false);

      await mockFileIsUploaded(component);

      expect(hasLogoSavedMessage(component)).toBe(true);
    });

    test('should no longer be displayed when a file is removed', async () => {
      const component = createComponent();

      await mockFileIsUploaded(component);
      expect(hasLogoSavedMessage(component)).toBe(true);

      await getRemoveButtonElement(component).trigger('click');
      expect(hasLogoSavedMessage(component)).toBe(false);
    });
  });

  describe('the message saying the logo was removed', () => {
    test('should be displayed when a file is removed', async () => {
      const component = createComponent({ hasLogo: true });

      expect(hasLogoRemovedMessage(component)).toBe(false);

      await getRemoveButtonElement(component).trigger('click');
      expect(hasLogoRemovedMessage(component)).toBe(true);
    });

    test('should no longer be displayed when a file is uploaded', async () => {
      const component = createComponent({ hasLogo: true });

      await getRemoveButtonElement(component).trigger('click');
      expect(hasLogoRemovedMessage(component)).toBe(true);

      await mockFileIsUploaded(component);
      expect(hasLogoRemovedMessage(component)).toBe(false);
    });
  });

  describe('when the user presses the remove button', () => {
    beforeEach(() => {
      fetchSpy.mockResolvedValue({
        ok: true,
      });
    });

    test('should make a DELETE request to the "deleteEndpoint" property', async () => {
      const component = createComponent({ hasLogo: true });

      expect(fetchSpy).not.toHaveBeenCalledWith('mocked-delete-endpoint');

      await getRemoveButtonElement(component).trigger('click');
      expect(fetchSpy).toHaveBeenNthCalledWith(1, 'mocked-delete-endpoint', {
        method: 'DELETE',
      });
    });

    test('should hide the logo and the remove button and display the label and the upload area', async () => {
      const component = createComponent({ hasLogo: true });

      expect(getImageElement(component).exists()).toBe(true);
      expect(getRemoveButtonElement(component).exists()).toBe(true);
      expect(getLabelElement(component).exists()).toBe(false);
      expect(getUploadAreaComponent(component).exists()).toBe(false);

      await getRemoveButtonElement(component).trigger('click');

      expect(getImageElement(component).exists()).toBe(false);
      expect(getRemoveButtonElement(component).exists()).toBe(false);
      expect(getLabelElement(component).exists()).toBe(true);
      expect(getUploadAreaComponent(component).exists()).toBe(true);
    });
  });
});
