import { SelectOptions } from '@admin-fe/form/interface';
import type { FileUploadLimit } from '@js/admin/utils/file/interface';
import { createMockedAttachmentType } from '@js/test';
import { flushPromises, mount } from '@vue/test-utils';
import {
  afterAll,
  afterEach,
  beforeEach,
  describe,
  expect,
  test,
  vi,
} from 'vitest';
import { ref, unref } from 'vue';
import { GroundOptions, PublicationFileTypes } from './interface';
import PublicationFiles from './PublicationFiles.vue';

const isFocusWithin = ref(true);

vi.mock('@js/admin/utils');

vi.mock('@vueuse/core', () => ({
  useFocusWithin: () => ({
    focused: isFocusWithin,
  }),
}));

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
    isFocusWithin.value = true;

    window.fetch = vi.fn().mockImplementation(() =>
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

  afterAll(() => {
    unref(isFocusWithin);
  });

  interface CreateComponentOptions {
    fileLimits: FileUploadLimit[];
    fileTypeOptions: PublicationFileTypes;
    groundOptions: GroundOptions;
    languageOptions: SelectOptions;
    maxLength: number;
    readableFileType?: string;
  }

  const createComponent = (options: Partial<CreateComponentOptions> = {}) => {
    const {
      fileLimits = [],
      maxLength = 2,
      fileTypeOptions = mockedPublicationFileTypes,
      groundOptions = [],
      languageOptions = [],
      readableFileType,
    } = options;

    return mount(PublicationFiles, {
      props: {
        canDelete: false,
        endpoint: 'https://mocked-endpoint.com',
        e2eName: 'mocked-e2e-name',
        fileLimits,
        fileTypeOptions,
        groundOptions,
        languageOptions,
        maxLength,
        readableFileType,
        uploadGroupId: 'mocked-upload-group-id',
      },
      global: {
        stubs: { Teleport: false },
        renderStubDefaultSlot: true,
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
    getChildComponent('Alert', component);

  const getPublicationFileFormComponent = (component = createComponent()) =>
    getChildComponent('PublicationFileForm', component);

  const clickAddFileButton = (component = createComponent()) =>
    getAddFileButton(component).trigger('click');

  const mockFileAsSaved = (component = createComponent(), id = 1) =>
    getPublicationFileFormComponent(component)?.vm.$emit(
      'saved',
      createMockedAtachment(id),
    );

  const mockFileAsDeleted = (component = createComponent(), id = 1) =>
    getFilesListComponent(component).vm.$emit('deleted', `mocked-id-${id}`);

  const mockEdit = (component = createComponent(), id = 1) =>
    getFilesListComponent(component).vm.$emit('edit', `mocked-id-${id}`);
  const mockCancel = (component = createComponent()) =>
    getPublicationFileFormComponent(component)?.vm.$emit('cancel');

  test('should make a request to fetch the current set of files', () => {
    createComponent();
    expect(window.fetch).toHaveBeenNthCalledWith(
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

    await flushPromises();

    expect(getFilesListComponent(component).props()).toMatchObject({
      files: new Map([['mocked-id-1', mockedFetchedAttachments[0]]]),
      canDelete: false,
      publicationFileTypes: mockedPublicationFileTypes,
      endpoint: 'https://mocked-endpoint.com',
    });
  });

  describe('the button to add a new file', () => {
    test('should be displayed if more files can be added', () => {
      const buttonElement = getAddFileButton();

      expect(buttonElement.text()).toBe('Bijlage toevoegen...');
      expect(buttonElement.attributes()).toEqual(
        expect.objectContaining({ 'aria-haspopup': 'dialog', type: 'button' }),
      );
    });

    test('should have a text saying to add another file when there is currently 1 or more files', async () => {
      const buttonElement = getAddFileButton();
      await flushPromises();

      expect(buttonElement.text()).toBe('Nog een bijlage toevoegen...');
    });

    test('should make the dialog visible', async () => {
      const component = createComponent();
      const dialogComponent = getDialogComponent(component);
      expect(dialogComponent.props('modelValue')).toBe(false);

      await clickAddFileButton(component);
      expect(dialogComponent.props('modelValue')).toBe(true);
    });
  });

  describe('the readable document type', () => {
    test('should equal the property "readableFileType" when it is provided', () => {
      const component = createComponent({
        maxLength: 1,
        readableFileType: 'mocked-readable-file-type',
      });

      expect(getAddFileButton(component).text()).toBe(
        'mocked-readable-file-type toevoegen...',
      );
      expect(getDialogComponent(component).props('title')).toBe(
        'mocked-readable-file-type toevoegen',
      );
    });

    test('should be based on the label of the provided document type when there is only one', () => {
      const component = createComponent({
        fileTypeOptions: [
          createMockedAttachmentType('advies-rapport', 'Adviesrapport'),
        ],
      });

      expect(getAddFileButton(component).text()).toBe(
        'Adviesrapport toevoegen...',
      );
      expect(getDialogComponent(component).props('title')).toBe(
        'Adviesrapport toevoegen',
      );
    });

    test('should be "Bijlage" by default', () => {
      const component = createComponent();

      expect(getAddFileButton(component).text()).toBe('Bijlage toevoegen...');
      expect(getDialogComponent(component).props('title')).toBe(
        'Bijlage toevoegen',
      );
    });
  });

  describe('when an file is deleted', () => {
    beforeEach(() => {
      isFocusWithin.value = false;
    });

    test('should display a message saying the file was deleted', async () => {
      const component = createComponent();
      await flushPromises();

      expect(getResultMessageComponent(component).exists()).toBe(false);

      await mockFileAsDeleted(component);
      expect(getResultMessageComponent(component)?.text()).toContain(
        "Bijlage 'mocked-name-1' is verwijderd",
      );
    });
  });

  describe('when a file is saved', () => {
    test('should display a message saying the file was saved', async () => {
      const component = createComponent();

      await flushPromises();
      await mockFileAsSaved(component);

      expect(getResultMessageComponent(component)?.text()).toContain(
        "Bijlage 'mocked-name-1' is toegevoegd",
      );
    });
  });

  describe('the dialog', () => {
    test('should be closed when the user clicks the "Cancel" button', async () => {
      const component = createComponent();
      const dialogComponent = getDialogComponent(component);

      await flushPromises();
      await clickAddFileButton(component);
      expect(dialogComponent.props('modelValue')).toBe(true);

      await mockCancel(component);
      expect(dialogComponent.props('modelValue')).toBe(false);
    });

    test('should have a title based on the readable document type and if a file is being edited or created', async () => {
      const component = createComponent();
      const dialogComponent = getDialogComponent(component);

      await flushPromises();
      await clickAddFileButton(component);
      expect(dialogComponent.props('title')).toBe('Bijlage toevoegen');

      await mockCancel(component);
      await mockEdit(component);
      expect(dialogComponent.props('title')).toBe('Bijlage bewerken');
    });

    test('should have an attribute "data-e2e-name" with the value of the "e2eName" property', () => {
      expect(getDialogComponent(createComponent()).props('e2eName')).toBe(
        'mocked-e2e-name',
      );
    });
  });
});
