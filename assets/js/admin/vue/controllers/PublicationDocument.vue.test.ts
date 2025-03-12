import type {
  GroundOptions,
  PublicationFileTypes,
} from '@admin-fe/component/publication/file/interface';
import { createMockedAttachmentType } from '@js/test';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { SelectOptions } from '../form/interface';
import PublicationDocument from './PublicationDocument.vue';

vi.mock('@js/admin/utils');

describe('The "PublicationDocument" component', () => {
  interface CreateComponentOptions {
    allowedFileTypes: string[];
    allowedMimeTypes: string[];
    fileTypeOptions: PublicationFileTypes;
    documentType: string;
    groundOptions: GroundOptions;
    languageOptions: SelectOptions;
  }

  const createMockedDocument = (name = 'mocked-name') => ({
    id: 'abc',
    internalReference: 'mocked-internal-reference',
    language: 'mocked-language',
    name,
    formalDate: 'mocked-formal-date',
    type: 'mocked-type',
    mimeType: 'mocked-mime-type',
    size: 1024,
    grounds: ['mocked-ground-1', 'mocked-ground-2'],
  });

  const mockedFetchedDocument = createMockedDocument();

  const mockReturnDocument = () => {
    global.fetch = vi.fn().mockImplementation(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockedFetchedDocument),
      }),
    );
  };

  const mockReturnNoDocument = () => {
    global.fetch = vi
      .fn()
      .mockImplementation(() =>
        Promise.reject(new Error('404: no document found')),
      );
  };

  beforeEach(() => {
    mockReturnNoDocument();

    HTMLDialogElement.prototype.close = vi.fn(); // This prevents a warning saying this function does not exist
    HTMLDialogElement.prototype.showModal = vi.fn(); // This prevents a warning saying this function does not exist
  });

  afterEach(() => {
    vi.resetAllMocks();
  });

  const createComponent = (options: Partial<CreateComponentOptions> = {}) => {
    const {
      allowedFileTypes = [],
      allowedMimeTypes = [],
      languageOptions = [],
      fileTypeOptions = [],
      groundOptions = [],
    } = options;

    return mount(PublicationDocument, {
      props: {
        allowedFileTypes,
        allowedMimeTypes,
        canDelete: false,
        languageOptions,
        fileTypeOptions,
        documentType: 'mocked-document-type',
        endpoint: 'https://mocked-endpoint.com',
        groundOptions,
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
    component.find('button');
  const getChildComponent = (
    componentName: string,
    component = createComponent(),
  ) => component.findComponent({ name: componentName });
  const getPublicationFileItemComponent = (component = createComponent()) =>
    getChildComponent('PublicationFileItem', component);
  const getDialogComponent = (component = createComponent()) =>
    getChildComponent('Dialog', component);
  const getAlertComponent = (component = createComponent()) =>
    getChildComponent('Alert', component);
  const getPublicationDocumentFormComponent = (component = createComponent()) =>
    getChildComponent('PublicationDocumentForm', component);

  test('should make a request to fetch the current document', () => {
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

  describe('when a document is already uploaded', () => {
    beforeEach(() => {
      mockReturnDocument();
    });

    afterEach(() => {
      mockReturnNoDocument();
    });

    test('should display the currently uploaded file', async () => {
      const fileTypeOptions = [
        createMockedAttachmentType('mocked-type-1', 'Mocked label 1'),
        createMockedAttachmentType('mocked-type-2', 'Mocked label 2'),
      ];

      const component = createComponent({ fileTypeOptions });
      await flushPromises();

      const publicationFileItemComponent =
        getPublicationFileItemComponent(component);

      expect(publicationFileItemComponent).toBeTruthy();
      expect(publicationFileItemComponent.props('canDelete')).toBe(false);
      expect(publicationFileItemComponent.props('date')).toBe(
        mockedFetchedDocument.formalDate,
      );
      expect(publicationFileItemComponent.props('endpoint')).toBe(
        'https://mocked-endpoint.com',
      );
      expect(publicationFileItemComponent.props('fileName')).toBe(
        mockedFetchedDocument.name,
      );
      expect(publicationFileItemComponent.props('fileSize')).toBe(
        mockedFetchedDocument.size,
      );
      expect(publicationFileItemComponent.props('fileTypes')).toEqual(
        fileTypeOptions,
      );
      expect(publicationFileItemComponent.props('fileTypeValue')).toBe(
        mockedFetchedDocument.type,
      );
      expect(publicationFileItemComponent.props('id')).toBe(
        mockedFetchedDocument.id,
      );
      expect(publicationFileItemComponent.props('mimeType')).toBe(
        mockedFetchedDocument.mimeType,
      );
    });

    test('should hide the button which allows to add a new document', async () => {
      const component = createComponent();

      await flushPromises();

      expect(getAddFileButton(component).exists()).toBeFalsy();
    });

    describe('when the document is deleted', () => {
      test('should display a message saying the file was deleted', async () => {
        const component = createComponent();
        await flushPromises();

        expect(getAlertComponent(component).exists()).toBe(false);

        await getPublicationFileItemComponent(component).vm.$emit('deleted');

        expect(getAlertComponent(component).text()).toContain(
          'mocked-document-type verwijderd',
        );
      });

      test('should no longer display the uploaded document', async () => {
        const component = createComponent();
        await flushPromises();

        const publicationFileItemComponent =
          getPublicationFileItemComponent(component);
        await publicationFileItemComponent.vm.$emit('deleted');

        expect(publicationFileItemComponent.exists()).toBe(false);
      });
    });
  });

  describe('the button to add a new document', () => {
    test('should be displayed when currently no document is uploaded yet', async () => {
      const buttonElement = getAddFileButton();

      await flushPromises();

      expect(buttonElement).toBeTruthy();
      expect(buttonElement.text()).toBe('+ mocked-document-type toevoegen...');
      expect(buttonElement.attributes()).toEqual(
        expect.objectContaining({ 'aria-haspopup': 'dialog', type: 'button' }),
      );
    });

    test('should make the dialog visible when pressing it', async () => {
      const component = createComponent();
      const buttonElement = getAddFileButton(component);
      const dialogComponent = getDialogComponent(component);

      expect(dialogComponent.props('modelValue')).toBe(false);

      await buttonElement.trigger('click');

      expect(dialogComponent.props('modelValue')).toBe(true);
    });
  });

  describe('when a document is saved', () => {
    test('should display a message saying the file was saved', async () => {
      const component = createComponent();

      await flushPromises();
      await getPublicationDocumentFormComponent(component).vm.$emit(
        'saved',
        createMockedDocument('mocked-new-name'),
      );

      expect(getAlertComponent(component).text()).toContain('toegevoegd');
    });
  });
});
