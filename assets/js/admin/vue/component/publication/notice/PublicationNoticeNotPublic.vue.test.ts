import { usePublicationFilesStore } from '@admin-fe/composables';
import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import PublicationNoticeNotPublic from './PublicationNoticeNotPublic.vue';
import type { Notice } from './interface';

vi.mock('@js/admin/utils', () => ({
  isSuccessStatusCode: (status: number) => status >= 200 && status < 300,
  validateResponse: vi.fn((request: Promise<Response>) => {
    return request.then((response: any) => response.json());
  }),
}));

describe('The "PublicationNoticeNotPublic" component', () => {
  const createMockedNotice = (overrides: Partial<Notice> = {}): Notice => ({
    id: 'mocked-id',
    dossier: { id: 'mocked-dossier-id' },
    documentName: 'mocked-document-name',
    formalDate: '2024-01-01',
    grounds: ['mocked-ground'],
    explanation: 'mocked-explanation',
    ...overrides,
  });

  interface Options {
    canDelete?: boolean;
    endpoint?: string;
    groundOptions?: Array<{ citation: string; label: string }>;
    hasMainDocument?: boolean;
  }

  const createComponent = (options: Partial<Options> = {}) =>
    mount(PublicationNoticeNotPublic, {
      props: {
        canDelete: options.canDelete ?? false,
        endpoint: options.endpoint ?? 'mocked-endpoint',
        groundOptions: options.groundOptions ?? [],
        hasMainDocument: options.hasMainDocument ?? false,
      },
      shallow: true,
      global: {
        stubs: {
          Teleport: false,
        },
        renderStubDefaultSlot: true,
      },
    });

  const getAddButton = (component = createComponent()) =>
    component.find('button[data-e2e-name="add-notice"]');

  const getEditButton = (component = createComponent()) =>
    component.find('button[data-e2e-name="edit-notice"]');

  const getDeleteButton = (component = createComponent()) =>
    component.find('button[data-e2e-name="delete-notice"]');

  const getNoticePreview = (component = createComponent()) =>
    component.find('[data-e2e-name="notice-preview"]');

  const getDialog = (component = createComponent()) =>
    component.findComponent({ name: 'Dialog' });

  const getForm = (component = createComponent()) =>
    component.findComponent({ name: 'PublicationNoticeNotPublicForm' });

  const getSuccessAlert = (component = createComponent()) =>
    component.find('[data-e2e-name="alerts"]');

  const getDeleteErrorAlert = (component = createComponent()) =>
    component.find('[data-e2e-name="delete-failed"]');

  beforeEach(() => {
    globalThis.fetch = vi.fn();
    HTMLDialogElement.prototype.showModal = vi.fn();
    HTMLDialogElement.prototype.close = vi.fn();
    vi.resetAllMocks();

    const store = usePublicationFilesStore();
    store.setHasMainDocument(false);
    store.setHasNotice(false);
  });

  describe('on mount', () => {
    test('should make a GET request to fetch the notice', () => {
      createComponent();

      expect(globalThis.fetch).toHaveBeenCalledWith('mocked-endpoint', {
        headers: {
          'Content-Type': 'application/json',
          accept: 'application/json',
        },
      });
    });

    test('should display the fetched notice details after fetching', async () => {
      const mockedNotice = createMockedNotice();
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockedNotice),
      });

      const component = createComponent();
      await flushPromises();

      expect(getNoticePreview(component).text()).toContain('1 januari 2024');
      expect(getNoticePreview(component).text()).toContain('1 weigeringsgrond');
      expect(getNoticePreview(component).text()).toContain(
        'en een toelichting',
      );
    });

    test('should not show a notice preview if fetch fails', async () => {
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.reject(new Error('Fetch failed')),
      });

      const component = createComponent();
      await flushPromises();

      expect(getNoticePreview(component).exists()).toBe(false);
    });
  });

  describe('when no notice exists', () => {
    test('should display an "add" button', () => {
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.reject(new Error('No notice')),
      });

      const component = createComponent();
      expect(getAddButton(component).exists()).toBe(true);
    });

    test('should disable the add button when hasMainDocument is true', async () => {
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.reject(new Error('No notice')),
      });

      const component = createComponent({ hasMainDocument: true });
      await flushPromises();

      expect(getAddButton(component).attributes('disabled')).toBeDefined();
    });

    test('should not disable the add button when hasMainDocument is false', async () => {
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.reject(new Error('No notice')),
      });

      const component = createComponent({ hasMainDocument: false });
      await flushPromises();

      expect(getAddButton(component).attributes('disabled')).toBeUndefined();
    });

    test('should reactively disable the add button when the store reports a main document is added', async () => {
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.reject(new Error('No notice')),
      });

      const component = createComponent({ hasMainDocument: false });
      await flushPromises();

      expect(getAddButton(component).attributes('disabled')).toBeUndefined();

      usePublicationFilesStore().setHasMainDocument(true);
      await flushPromises();

      expect(getAddButton(component).attributes('disabled')).toBeDefined();
    });
  });

  describe('when a notice exists', () => {
    test('should display the formal date', async () => {
      const mockedNotice = createMockedNotice({ formalDate: '2024-05-12' });
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockedNotice),
      });

      const component = createComponent();
      await flushPromises();

      expect(getNoticePreview(component).text()).toContain('12 mei 2024');
    });

    test('should display the ground count', async () => {
      const mockedNotice = createMockedNotice({
        grounds: ['ground1', 'ground2', 'ground3'],
      });
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockedNotice),
      });

      const component = createComponent();
      await flushPromises();

      expect(getNoticePreview(component).text()).toContain('3 weigeringsgrond');
    });

    test('should display that an explanation was provided', async () => {
      const mockedNotice = createMockedNotice({
        explanation: 'mocked-explanation-text',
      });
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockedNotice),
      });

      const component = createComponent();
      await flushPromises();

      expect(getNoticePreview(component).text()).toContain(
        'en een toelichting',
      );
    });

    test('should display the edit button', async () => {
      const mockedNotice = createMockedNotice();
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockedNotice),
      });

      const component = createComponent();
      await flushPromises();

      expect(getEditButton(component).exists()).toBe(true);
    });

    test('should not display the delete button when canDelete is false', async () => {
      const mockedNotice = createMockedNotice();
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockedNotice),
      });

      const component = createComponent({ canDelete: false });
      await flushPromises();

      expect(getDeleteButton(component).exists()).toBe(false);
    });

    test('should display the delete button when canDelete is true', async () => {
      const mockedNotice = createMockedNotice();
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockedNotice),
      });

      const component = createComponent({ canDelete: true });
      await flushPromises();

      expect(getDeleteButton(component).exists()).toBe(true);
    });
  });

  describe('add button interaction', () => {
    test('should open the dialog when clicked', async () => {
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.reject(new Error('No notice')),
      });

      const component = createComponent();
      await flushPromises();
      await getAddButton(component).trigger('click');

      expect(getDialog(component).props('modelValue')).toBe(true);
    });

    test('should set dialog title to "toevoegen" when opening to add', async () => {
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.reject(new Error('No notice')),
      });

      const component = createComponent();
      await flushPromises();
      await getAddButton(component).trigger('click');

      expect(getDialog(component).props('title')).toBe('Mededeling toevoegen');
    });
  });

  describe('edit button interaction', () => {
    test('should open the dialog when clicked', async () => {
      const mockedNotice = createMockedNotice();
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockedNotice),
      });

      const component = createComponent();
      await flushPromises();
      await getEditButton(component).trigger('click');

      expect(getDialog(component).props('modelValue')).toBe(true);
    });

    test('should set dialog title to "bewerken" when opening to edit', async () => {
      const mockedNotice = createMockedNotice();
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockedNotice),
      });

      const component = createComponent();
      await flushPromises();
      await getEditButton(component).trigger('click');

      expect(getDialog(component).props('title')).toBe('Mededeling bewerken');
    });
  });

  describe('form cancellation', () => {
    test('should close the dialog when form emits cancel', async () => {
      const mockedNotice = createMockedNotice();
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockedNotice),
      });

      const component = createComponent();
      await flushPromises();
      await getEditButton(component).trigger('click');

      expect(getDialog(component).props('modelValue')).toBe(true);

      await getForm(component)?.vm.$emit('cancel');

      expect(getDialog(component).props('modelValue')).toBe(false);
    });
  });

  describe('form submission', () => {
    test('should close the dialog when form emits saved', async () => {
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.reject(new Error('No notice')),
      });

      const component = createComponent();
      await flushPromises();
      await getAddButton(component).trigger('click');

      expect(getDialog(component).props('modelValue')).toBe(true);

      const savedNotice = createMockedNotice();
      await getForm(component)?.vm.$emit('saved', savedNotice);

      expect(getDialog(component).props('modelValue')).toBe(false);
    });

    test('should show success alert with "toegevoegd" text after create', async () => {
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.reject(new Error('No notice')),
      });

      const component = createComponent();
      await flushPromises();
      await getAddButton(component).trigger('click');

      const savedNotice = createMockedNotice();
      await getForm(component)?.vm.$emit('saved', savedNotice);

      expect(getSuccessAlert(component).text()).toContain('toegevoegd');
    });

    test('should show success alert with "bijgewerkt" text after update', async () => {
      const mockedNotice = createMockedNotice();
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockedNotice),
      });

      const component = createComponent();
      await flushPromises();
      await getEditButton(component).trigger('click');

      const updatedNotice = createMockedNotice({ id: 'updated-id' });
      await getForm(component)?.vm.$emit('saved', updatedNotice);

      expect(getSuccessAlert(component).text()).toContain('bijgewerkt');
    });
  });

  describe('delete button interaction', () => {
    test('should make a DELETE request when delete button is clicked', async () => {
      const mockedNotice = createMockedNotice();
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockedNotice),
      });

      const component = createComponent({ canDelete: true });
      await flushPromises();

      (globalThis.fetch as any).mockResolvedValueOnce({ status: 200 });
      await getDeleteButton(component).trigger('click');

      expect(globalThis.fetch).toHaveBeenCalledWith(
        'mocked-endpoint',
        expect.objectContaining({
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json',
            accept: 'application/json',
          },
        }),
      );
    });

    test('should show error alert when delete fails', async () => {
      const mockedNotice = createMockedNotice();
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockedNotice),
      });

      const component = createComponent({ canDelete: true });
      await flushPromises();

      (globalThis.fetch as any).mockResolvedValueOnce({ status: 500 });
      await getDeleteButton(component).trigger('click');

      expect(getDeleteErrorAlert(component).exists()).toBe(true);
    });

    test('should set notice to null after successful delete', async () => {
      const mockedNotice = createMockedNotice();
      (globalThis.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockedNotice),
      });

      const component = createComponent({ canDelete: true });
      await flushPromises();

      expect(getNoticePreview(component).exists()).toBe(true);

      (globalThis.fetch as any).mockResolvedValueOnce({ status: 200 });
      await getDeleteButton(component).trigger('click');

      expect(getNoticePreview(component).exists()).toBe(false);
    });
  });
});
