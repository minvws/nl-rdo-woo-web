import PublicationDocument from '@admin-fe/controllers/PublicationDocument.vue';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';

describe('The "PublicationDocument" component', () => {
  interface CreateComponentOptions {
    allowedFileTypes: string[];
    allowedMimeTypes: string[];
    documentLanguageOptions: string[];
    documentTypeOptions: string[];
    groundOptions: string[];
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
    global.fetch = vi.fn().mockImplementation(() => Promise.resolve(
      { status: 200, json: () => Promise.resolve(mockedFetchedDocument) },
    ));
  };

  const mockReturnNoDocument = () => {
    global.fetch = vi.fn().mockImplementation(() => Promise.reject(new Error('404: no document found')));
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
      allowedFileTypes = [], allowedMimeTypes = [], documentLanguageOptions = [], documentTypeOptions = [], groundOptions = [],
    } = options;

    return mount(PublicationDocument, {
      props: {
        allowedFileTypes,
        allowedMimeTypes,
        canDelete: false,
        documentLanguageOptions,
        documentTypeOptions,
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

  const getAddDocumentButton = (component = createComponent()) => component.find('button');
  const getChildComponent = (componentName: string, component = createComponent()) => component.findComponent({ name: componentName });
  const getUploadedAttachmentComponent = (component = createComponent()) => getChildComponent('UploadedAttachment', component);
  const getDialogComponent = (component = createComponent()) => getChildComponent('Dialog', component);
  const getAlertComponent = (component = createComponent()) => getChildComponent('Alert', component);
  const getPublicationDocumentFormComponent = (component = createComponent()) => getChildComponent('PublicationDocumentForm', component);

  test('should make a request to fetch the current document', () => {
    createComponent();
    expect(global.fetch).toHaveBeenNthCalledWith(1, 'https://mocked-endpoint.com', {
      headers: { 'Content-Type': 'application/json', accept: 'application/json' },
    });
  });

  describe('when a document is already uploaded', () => {
    beforeEach(() => {
      mockReturnDocument();
    });

    afterEach(() => {
      mockReturnNoDocument();
    });

    test('should display the currently uploaded document', async () => {
      const documentTypeOptions = ['mocked-document-type-1', 'mocked-document-type-2'];

      const component = createComponent({ documentTypeOptions });
      await flushPromises();

      const uploadedAttachmentComponent = getUploadedAttachmentComponent(component);

      expect(uploadedAttachmentComponent).toBeTruthy();
      expect(uploadedAttachmentComponent.props('canDelete')).toBe(false);
      expect(uploadedAttachmentComponent.props('date')).toBe(mockedFetchedDocument.formalDate);
      expect(uploadedAttachmentComponent.props('documentType')).toBe(mockedFetchedDocument.type);
      expect(uploadedAttachmentComponent.props('documentTypes')).toEqual(documentTypeOptions);
      expect(uploadedAttachmentComponent.props('endpoint')).toBe('https://mocked-endpoint.com');
      expect(uploadedAttachmentComponent.props('fileName')).toBe(mockedFetchedDocument.name);
      expect(uploadedAttachmentComponent.props('fileSize')).toBe(mockedFetchedDocument.size);
      expect(uploadedAttachmentComponent.props('id')).toBe(mockedFetchedDocument.id);
      expect(uploadedAttachmentComponent.props('mimeType')).toBe(mockedFetchedDocument.mimeType);
    });

    test('should hide the button which allows to add a new document', async () => {
      const component = createComponent();

      await flushPromises();

      expect(getAddDocumentButton(component).exists()).toBeFalsy();
    });

    describe('when the document is deleted', () => {
      test('should display a message saying the attachment was deleted', async () => {
        const component = createComponent();
        await flushPromises();

        expect(getAlertComponent(component).exists()).toBe(false);

        const uploadedAttachmentComponent = getUploadedAttachmentComponent(component);
        await uploadedAttachmentComponent.vm.$emit('deleted');

        const alertComponent = getAlertComponent(component);
        expect(alertComponent.exists()).toBe(true);
        expect(alertComponent.text()).toContain('mocked-document-type verwijderd');
      });

      test('should no longer display the uploaded document', async () => {
        const component = createComponent();
        await flushPromises();

        const uploadedAttachmentComponent = getUploadedAttachmentComponent(component);
        await uploadedAttachmentComponent.vm.$emit('deleted');

        expect(uploadedAttachmentComponent.exists()).toBe(false);
      });
    });
  });

  describe('the button to add a new document', () => {
    test('should be displayed when currently no document is uploaded yet', async () => {
      const buttonElement = getAddDocumentButton();

      await flushPromises();

      expect(buttonElement).toBeTruthy();
      expect(buttonElement.text()).toBe('+ mocked-document-type toevoegen...');
      expect(buttonElement.attributes()).toEqual(expect.objectContaining({ 'aria-haspopup': 'dialog', type: 'button' }));
    });

    test('should make the dialog visible when pressing it', async () => {
      const component = createComponent();
      const buttonElement = getAddDocumentButton(component);
      const dialogComponent = getDialogComponent(component);

      expect(dialogComponent.props('modelValue')).toBe(false);

      await buttonElement.trigger('click');

      expect(dialogComponent.props('modelValue')).toBe(true);
    });
  });

  describe('when a document is saved', () => {
    test('should display a message saying the attachment was saved', async () => {
      const component = createComponent();

      await flushPromises();

      const publicationDocumentFormComponent = getPublicationDocumentFormComponent(component);
      await publicationDocumentFormComponent.vm.$emit('saved', createMockedDocument('mocked-new-name'));

      const alertComponent = getAlertComponent(component);
      expect(alertComponent.text()).toContain('toegevoegd');
    });
  });
});
