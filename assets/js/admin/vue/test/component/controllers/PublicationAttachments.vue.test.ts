import PublicationAttachments from '@admin-fe/controllers/PublicationAttachments.vue';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';

describe('The "PublicationAttachments" component', () => {
  interface CreateComponentOptions {
    allowedFileTypes: string[];
    allowedMimeTypes: string[];
    documentLanguageOptions: string[];
    documentTypeOptions: string[];
    groundOptions: string[];
  }

  const createMockedAtachment = (name = 'mocked-name') => ({
    id: 'abc',
    internalReference: 'mocked-internal-reference',
    language: 'mocked-language',
    name,
    formalDate: 'mocked-formal-date',
    type: 'mocked-type',
    grounds: ['mocked-ground-1', 'mocked-ground-2'],
  });

  const mockedFetchedAttachments = [createMockedAtachment()];

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

  const createComponent = (options: Partial<CreateComponentOptions> = {}) => {
    const {
      allowedFileTypes = [],
      allowedMimeTypes = [],
      documentLanguageOptions = [],
      documentTypeOptions = [],
      groundOptions = [],
    } = options;

    return mount(PublicationAttachments, {
      props: {
        allowedFileTypes,
        allowedMimeTypes,
        canDelete: false,
        documentLanguageOptions,
        documentTypeOptions,
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

  const getAddAttachmentButton = (component = createComponent()) =>
    component.get('button');
  const getChildComponent = (
    componentName: string,
    component = createComponent(),
  ) => component.findComponent({ name: componentName });
  const getAttachmentsListComponent = (component = createComponent()) =>
    getChildComponent('AttachmentsList', component);
  const getDialogComponent = (component = createComponent()) =>
    getChildComponent('Dialog', component);
  const getAlertComponent = (component = createComponent()) =>
    getChildComponent('Alert', component);
  const getPublicationAttachmentsFormComponent = (
    component = createComponent(),
  ) => getChildComponent('PublicationAttachmentsForm', component);

  test('should make a request to fetch the current set of attachments', () => {
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

  test('should display a list of uploaded attachments', async () => {
    const documentTypeOptions = [
      'mocked-document-type-1',
      'mocked-document-type-2',
    ];

    const component = createComponent({ documentTypeOptions });
    const attachmentListComponent = getAttachmentsListComponent(component);

    await flushPromises();

    expect(attachmentListComponent).toBeTruthy();
    expect(attachmentListComponent.props('attachments')).toEqual(
      new Map([['abc', mockedFetchedAttachments[0]]]),
    );
    expect(attachmentListComponent.props('canDelete')).toBe(false);
    expect(attachmentListComponent.props('documentTypes')).toEqual(
      documentTypeOptions,
    );
    expect(attachmentListComponent.props('endpoint')).toBe(
      'https://mocked-endpoint.com',
    );
  });

  describe('the button to add a new attachment', () => {
    test('should be displayed', () => {
      const buttonElement = getAddAttachmentButton();

      expect(buttonElement).toBeTruthy();
      expect(buttonElement.text()).toBe('+ Bijlage toevoegen...');
      expect(buttonElement.attributes()).toEqual(
        expect.objectContaining({ 'aria-haspopup': 'dialog', type: 'button' }),
      );
    });

    test('should have a text saying to add another attachment when there is currently 1 or more attachments', async () => {
      const buttonElement = getAddAttachmentButton();

      await flushPromises();

      expect(buttonElement.text()).toBe('+ Nog een bijlage toevoegen...');
    });

    test('should make the dialog visible', async () => {
      const component = createComponent();
      const buttonElement = getAddAttachmentButton(component);
      const dialogComponent = getDialogComponent(component);

      expect(dialogComponent.props('modelValue')).toBe(false);

      await buttonElement.trigger('click');

      expect(dialogComponent.props('modelValue')).toBe(true);
    });
  });

  describe('when an attachment is deleted', () => {
    test('should display a message saying the attachment was deleted', async () => {
      const component = createComponent();
      const attachmentListComponent = getAttachmentsListComponent(component);

      await flushPromises();

      expect(getAlertComponent(component).exists()).toBe(false);

      await attachmentListComponent.vm.$emit('deleted', 'abc');

      const alertComponent = getAlertComponent(component);
      expect(alertComponent.exists()).toBe(true);
      expect(alertComponent.text()).toContain(
        "Bijlage 'mocked-name' is verwijderd",
      );
    });
  });

  describe('when an attachment is saved', () => {
    test('should display a message saying the attachment was saved', async () => {
      const component = createComponent();
      const attachmentListComponent = getAttachmentsListComponent(component);

      await flushPromises();

      await attachmentListComponent.vm.$emit('edit', 1);

      const publicationAttachmentsFormComponent =
        getPublicationAttachmentsFormComponent(component);
      expect(publicationAttachmentsFormComponent.exists()).toBe(true);
      await publicationAttachmentsFormComponent.vm.$emit(
        'saved',
        createMockedAtachment('mocked-new-name'),
      );

      const alertComponent = getAlertComponent(component);
      expect(alertComponent.text()).toContain(
        "Bijlage 'mocked-new-name' is toegevoegd",
      );
    });
  });
});
