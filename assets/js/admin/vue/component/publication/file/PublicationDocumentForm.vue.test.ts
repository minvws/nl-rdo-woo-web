import { FormValue } from '@admin-fe/form/interface';
import { createMockedPublicationFile } from '@js/test';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import { nextTick } from 'vue';
import PublicationDocumentForm from './PublicationDocumentForm.vue';

let formSubmitFunction: (formValue: FormValue) => Promise<Response>;

const mockedInputStore = {
  setValidators: vi.fn(),
  setValue: vi.fn(),
};

vi.mock('@admin-fe/composables', () => ({
  useFormStore: vi.fn((submitFunction) => {
    formSubmitFunction = submitFunction;
    return {
      getInputStore: vi.fn().mockReturnValue(mockedInputStore),
      reset: vi.fn(),
    };
  }),
}));

describe('The "PublicationDocumentForm" component', () => {
  const createComponent = () =>
    mount(PublicationDocumentForm, {
      props: {
        allowedFileTypes: ['mocked-file-type-1', 'mocked-file-type-2'],
        allowedMimeTypes: ['mocked/mime-type-1', 'mocked/mime-type-2'],
        document: createMockedPublicationFile(),
        languageOptions: [{ label: 'Dutch', value: 'nl' }],
        endpoint: 'mocked-endpoint',
        groundOptions: [{ citation: 'mocked-citation', label: 'mocked-label' }],
        fileTypeOptions: [],
      },
      shallow: true,
      global: {
        renderStubDefaultSlot: true,
      },
    });

  const getFormComponent = (component = createComponent()) =>
    component.findComponent({ name: 'Form' });

  const getAlertComponent = (component = createComponent()) =>
    component.findComponent({ name: 'Alert' });

  const getCancelButton = (component = createComponent()) =>
    component.findAllComponents({ name: 'FormButton' }).at(1);

  const getFileTypesComponent = (component = createComponent()) =>
    component.findComponent({ name: 'InputFileTypes' });

  const getFileUploadComponent = (component = createComponent()) =>
    component.findComponent({ name: 'InputFileUpload' });

  const getLanguagesComponent = (component = createComponent()) =>
    component.findComponent({ name: 'InputLanguages' });

  const getGroundsComponent = (component = createComponent()) =>
    component.findComponent({ name: 'InputGrounds' });

  const getReferenceComponent = (component = createComponent()) =>
    component.findComponent({ name: 'InputReference' });

  beforeEach(() => {
    vi.resetAllMocks();

    global.fetch = vi.fn();
  });

  test('should display a file upload field', () => {
    expect(getFileUploadComponent().props()).toMatchObject({
      allowedFileTypes: ['mocked-file-type-1', 'mocked-file-type-2'],
      allowedMimeTypes: ['mocked/mime-type-1', 'mocked/mime-type-2'],
      fileInfo: null,
      groupId: '',
      displayMaxOneFileMessage: true,
    });
  });

  test('should allow the user to provide the internal reference of the file', () => {
    expect(getReferenceComponent().props()).toMatchObject({
      value: 'mocked-internal-reference',
    });
  });

  test('should allow the user to provide the type of this file', () => {
    expect(getFileTypesComponent().props()).toMatchObject({
      options: [],
      value: 'mocked-type',
    });
  });

  test('should allow the user to provide the language of this file', () => {
    expect(getLanguagesComponent().props()).toMatchObject({
      options: [{ label: 'Dutch', value: 'nl' }],
      value: 'Dutch',
    });
  });

  test('should allow the user to provide grounds for this file', () => {
    expect(getGroundsComponent().props()).toMatchObject({
      options: [{ citation: 'mocked-citation', label: 'mocked-label' }],
      values: ['mocked-ground-1', 'mocked-ground-2'],
    });
  });

  test('should display an error message when saving the file fails', async () => {
    const component = createComponent();

    expect(getAlertComponent(component).exists()).toBe(false);

    await getFormComponent(component).vm.$emit('submitError');
    expect(getAlertComponent(component).exists()).toBe(true);
  });

  describe('when pressing the cancel button', () => {
    test('should emit a "cancel" event', async () => {
      const component = createComponent();
      await getCancelButton(component)?.trigger('click');
      expect(component.emitted('cancel')).toBeTruthy();
    });

    test('should hide the error message', async () => {
      const component = createComponent();

      await getFormComponent(component).vm.$emit('submitError');
      expect(getAlertComponent(component).exists()).toBe(true);

      await getCancelButton(component)?.trigger('click');
      expect(getAlertComponent(component).exists()).toBe(false);
    });
  });

  describe('wghen submitting the form', () => {
    test('should make a request to the endpoint with the correct arguments', async () => {
      formSubmitFunction({
        name: 'mocked-name',
        size: 100,
        type: 'mocked-type',
      });

      expect(global.fetch).toHaveBeenCalledWith(
        'mocked-endpoint',
        expect.objectContaining({
          body: JSON.stringify({
            name: 'mocked-name',
            size: 100,
            type: 'mocked-type',
          }),
          headers: {
            'Content-Type': 'application/json',
            accept: 'application/json',
          },
          method: 'POST',
        }),
      );
    });
  });
  describe('when the property "isEditMode" or "document" of the component changes', () => {
    test('should update the file info', async () => {
      const component = createComponent();
      expect(getFileUploadComponent(component).props()).toMatchObject({
        fileInfo: null,
      });

      const updatedDocument = createMockedPublicationFile({
        name: 'updated-name',
        size: 200,
        mimeType: 'updated-mime-type',
      });
      await component.setProps({ document: updatedDocument });
      await nextTick();
      expect(getFileUploadComponent(component).props()).toMatchObject({
        fileInfo: {
          name: 'updated-name',
          size: 200,
          type: 'updated-mime-type',
        },
      });
    });

    test('should reset the validators of the uploadUuid input store if "isEditMode" is true', async () => {
      const component = createComponent();

      expect(mockedInputStore.setValidators).not.toHaveBeenCalled();

      await component.setProps({ isEditMode: true });
      await nextTick();

      expect(mockedInputStore.setValidators).toHaveBeenCalledWith([]);
      expect(mockedInputStore.setValue).toHaveBeenCalledWith(undefined);
    });
  });
});
