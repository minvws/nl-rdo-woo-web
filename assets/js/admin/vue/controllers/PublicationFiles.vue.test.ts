import {
  GroundOptions,
  PublicationFileTypes,
} from '@admin-fe/component/publication/file/interface';
import { createMockedAttachmentType } from '@js/test';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { SelectOptions } from '../form/interface';
import PublicationFiles from './PublicationFiles.vue';

vi.mock('@js/admin/utils');

describe('The "PublicationFiles" component', () => {
  const createMockedAtachment = (id: number) => ({
    id: `mocked-id-${id}`,
    internalReference: `mocked-internal-reference-${id}`,
    language: 'mocked-language',
    name: `mocked-name-${id}`,
    formalDate: 'mocked-formal-date',
    type: `mocked-type-value-${id}`,
    grounds: ['mocked-ground-1', 'mocked-ground-2'],
  });

  const mockedFetchedAttachments = [
    createMockedAtachment(1),
    createMockedAtachment(2),
  ];
  const mockedPublicationFileTypes: PublicationFileTypes = [
    createMockedAttachmentType('mocked-type-value-1', 'mocked-type-label-1'),
    createMockedAttachmentType('mocked-type-value-3', 'mocked-type-label-3'),
  ];

  beforeEach(() => {
    global.fetch = vi.fn().mockImplementation(() =>
      Promise.resolve({
        json: () => Promise.resolve(mockedFetchedAttachments),
      }),
    );
    HTMLDialogElement.prototype.close = vi.fn(); // This prevents a warning saying this function does not exist
    HTMLDialogElement.prototype.showModal = vi.fn(); // This prevents a warning saying this function does not exist
  });

  afterEach(() => {
    vi.resetAllMocks();
  });

  interface CreateComponentOptions {
    allowedFileTypes: string[];
    allowedMimeTypes: string[];
    allowMultiple: boolean;
    fileTypeOptions: PublicationFileTypes;
    groundOptions: GroundOptions;
    languageOptions: SelectOptions;
  }

  const createComponent = (options: Partial<CreateComponentOptions> = {}) => {
    const {
      allowedFileTypes = [],
      allowedMimeTypes = [],
      allowMultiple = true,
      fileTypeOptions = mockedPublicationFileTypes,
      groundOptions = [],
      languageOptions = [],
    } = options;

    return mount(PublicationFiles, {
      props: {
        allowedFileTypes,
        allowedMimeTypes,
        allowMultiple,
        canDelete: false,
        endpoint: 'https://mocked-endpoint.com',
        fileTypeOptions,
        groundOptions,
        languageOptions,
        uploadGroupId: 'mocked-upload-group-id',
      },
      global: {
        stubs: {
          Alert: false,
          Dialog: false,
          Teleport: false,
        },
      },
      shallow: true,
    });
  };

  const getAddFileButton = (component = createComponent()) =>
    component.get('button');
  const getChildComponent = (
    componentName: string,
    component = createComponent(),
  ) => component.findComponent({ name: componentName });
  const getFilesListComponent = (component = createComponent()) =>
    getChildComponent('PublicationFilesList', component);
  const getDialogComponent = (component = createComponent()) =>
    getChildComponent('Dialog', component);
  const getResultMessageComponent = (component = createComponent()) =>
    findAlertComponent(component, 'success');
  const getOnlyOneAllowedMessageComponent = (component = createComponent()) =>
    findAlertComponent(component, 'danger');
  const findAlertComponent = (
    component = createComponent(),
    type: 'success' | 'danger' = 'success',
  ) =>
    component
      .findAllComponents({ name: 'Alert' })
      .find((alert) => alert.props('type') === type);
  const getPublicationFileFormComponent = (component = createComponent()) =>
    getChildComponent('PublicationFileForm', component);

  const mockFileAsSaved = (component = createComponent()) => {
    const PublicationFileFormComponent =
      getPublicationFileFormComponent(component);
    return PublicationFileFormComponent.vm.$emit(
      'saved',
      createMockedAtachment(1),
    );
  };

  test('should make a request to fetch the current set of files', () => {
    createComponent();
    expect(global.fetch).toHaveBeenNthCalledWith(
      1,
      'https://mocked-endpoint.com',
      {
        headers: {
          'Content-Type': 'application/json',
          accept: 'application/json',
        },
      },
    );
  });

  test('should display a list of uploaded files and filter them based on the provided document types', async () => {
    const component = createComponent();
    const fileListComponent = getFilesListComponent(component);

    await flushPromises();

    expect(fileListComponent).toBeTruthy();
    expect(fileListComponent.props('files')).toEqual(
      new Map([['mocked-id-1', mockedFetchedAttachments[0]]]),
    );
    expect(fileListComponent.props('canDelete')).toBe(false);
    expect(fileListComponent.props('publicationFileTypes')).toEqual(
      mockedPublicationFileTypes,
    );
    expect(fileListComponent.props('endpoint')).toBe(
      'https://mocked-endpoint.com',
    );
  });

  describe('the button to add a new file', () => {
    test('should be displayed', () => {
      const buttonElement = getAddFileButton();

      expect(buttonElement).toBeTruthy();
      expect(buttonElement.text()).toBe('+ Bijlage toevoegen...');
      expect(buttonElement.attributes()).toEqual(
        expect.objectContaining({ 'aria-haspopup': 'dialog', type: 'button' }),
      );
    });

    test('should have a text saying to add another file when there is currently 1 or more files', async () => {
      const buttonElement = getAddFileButton();

      await flushPromises();

      expect(buttonElement.text()).toBe('+ Nog een bijlage toevoegen...');
    });

    test('should make the dialog visible', async () => {
      const component = createComponent();
      const buttonElement = getAddFileButton(component);
      const dialogComponent = getDialogComponent(component);

      expect(dialogComponent.props('modelValue')).toBe(false);

      await buttonElement.trigger('click');

      expect(dialogComponent.props('modelValue')).toBe(true);
    });
  });

  describe('the readable document type', () => {
    test('should be "Bijlage" by default', () => {
      const component = createComponent();

      expect(getAddFileButton(component).text()).toBe('+ Bijlage toevoegen...');
      expect(getDialogComponent(component).props('title')).toBe(
        'Bijlage toevoegen',
      );
    });

    test('should be based on the label of the provided document type when there is only one', () => {
      const component = createComponent({
        fileTypeOptions: [
          createMockedAttachmentType('advies-rapport', 'Adviesrapport'),
        ],
      });

      expect(getAddFileButton(component).text()).toBe(
        '+ Adviesrapport toevoegen...',
      );
      expect(getDialogComponent(component).props('title')).toBe(
        'Adviesrapport toevoegen',
      );
    });
  });

  describe('when an file is deleted', () => {
    test('should display a message saying the file was deleted', async () => {
      const component = createComponent();
      const fileListComponent = getFilesListComponent(component);

      await flushPromises();

      expect(getResultMessageComponent(component)).toBeFalsy();

      await fileListComponent.vm.$emit('deleted', 'mocked-id-1');

      const alertComponent = getResultMessageComponent(component);
      expect(alertComponent?.exists()).toBe(true);
      expect(alertComponent?.text()).toContain(
        "Bijlage 'mocked-name-1' is verwijderd",
      );
    });
  });

  describe('when a file is saved', () => {
    test('should display a message saying the file was saved', async () => {
      const component = createComponent();
      const fileListComponent = getFilesListComponent(component);

      await flushPromises();

      await fileListComponent.vm.$emit('edit', 1);
      await mockFileAsSaved(component);

      expect(getResultMessageComponent(component)?.text()).toContain(
        "Bijlage 'mocked-name-1' is toegevoegd",
      );
    });
  });

  describe('the message saying that only one file is allowed', () => {
    test('should not be displayed by default', () => {
      const component = createComponent({ allowMultiple: false });
      expect(getOnlyOneAllowedMessageComponent(component)).toBeFalsy();
    });

    test('should be displayed when pressing the "Add file" button while there already is an file', async () => {
      const component = createComponent({ allowMultiple: false });
      const buttonElement = getAddFileButton(component);

      expect(getOnlyOneAllowedMessageComponent(component)).toBeFalsy();

      await buttonElement.trigger('click');

      // Should still be hidden because there is no file yet
      expect(getOnlyOneAllowedMessageComponent(component)).toBeFalsy();

      await mockFileAsSaved(component);
      await buttonElement.trigger('click');

      // Should now be visible because there already is an file
      expect(getOnlyOneAllowedMessageComponent(component)).toBeTruthy();
    });

    test('should be hidden again when closing the dialog', async () => {
      const component = createComponent({ allowMultiple: false });
      const buttonElement = getAddFileButton(component);

      await mockFileAsSaved(component);
      await buttonElement.trigger('click');

      expect(getOnlyOneAllowedMessageComponent(component)).toBeTruthy();

      await getDialogComponent(component).vm.$emit('close');

      expect(getOnlyOneAllowedMessageComponent(component)).toBeFalsy();
    });
  });
});
