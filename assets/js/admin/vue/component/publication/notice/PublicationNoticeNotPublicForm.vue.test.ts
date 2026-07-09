import type { FormValue } from '@admin-fe/form/interface';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import { nextTick } from 'vue';
import { mount } from '@vue/test-utils';
import PublicationNoticeNotPublicForm from './PublicationNoticeNotPublicForm.vue';
import type { Notice } from './interface';

let formSubmitFunction: (
  formValue: FormValue,
  dirtyFormValue: FormValue,
) => Promise<Response>;

vi.mock('@admin-fe/composables', () => ({
  useFormStore: vi.fn((submitFunction) => {
    formSubmitFunction = submitFunction;
    return {
      reset: vi.fn(),
    };
  }),
}));

vi.mock('@js/admin/utils', () => ({
  isSuccessStatusCode: (status: number) => status >= 200 && status < 300,
}));

describe('The "PublicationNoticeNotPublicForm" component', () => {
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
    endpoint?: string;
    groundOptions?: Array<{ citation: string; label: string }>;
    isEditMode?: boolean;
    notice?: Notice | null;
  }

  const createComponent = (options: Partial<Options> = {}) =>
    mount(PublicationNoticeNotPublicForm, {
      props: {
        endpoint: options.endpoint ?? 'mocked-endpoint',
        groundOptions: options.groundOptions ?? [],
        isEditMode: options.isEditMode ?? false,
        notice: options.notice ?? null,
      },
      shallow: true,
      global: {
        renderStubDefaultSlot: true,
      },
    });

  const getFormComponent = (component = createComponent()) =>
    component.findComponent({ name: 'Form' });

  const getInputTextComponent = (component = createComponent()) =>
    component.findComponent({ name: 'InputText' });

  const getInputDateComponent = (component = createComponent()) =>
    component.findComponent({ name: 'InputDate' });

  const getInputGroundsComponent = (component = createComponent()) =>
    component.findComponent({ name: 'InputGrounds' });

  const getInputTextareaComponent = (component = createComponent()) =>
    component.findComponent({ name: 'InputTextarea' });

  const getSaveButton = (component = createComponent()) =>
    component.findAllComponents({ name: 'FormButton' }).at(0);

  const getDeleteButton = (component = createComponent()) => {
    const buttons = component.findAllComponents({ name: 'FormButton' });
    return component.props('isEditMode') ? buttons.at(1) : undefined;
  };

  const getCancelButton = (component = createComponent()) =>
    component.findAllComponents({ name: 'FormButton' }).at(-1);

  const getSaveFailedAlert = (component = createComponent()) =>
    component.find('[data-e2e-name="save-failed"]');

  beforeEach(() => {
    globalThis.fetch = vi.fn();
    vi.resetAllMocks();
  });

  describe('form fields', () => {
    test('should display InputText for documentName with value from notice', () => {
      const notice = createMockedNotice({ documentName: 'mocked-doc-name' });
      const component = createComponent({ notice });

      expect(getInputTextComponent(component).props()).toMatchObject({
        value: 'mocked-doc-name',
        name: 'documentName',
        label: 'Documentnaam',
      });
    });

    test('should display InputDate with value from notice.formalDate', () => {
      const notice = createMockedNotice({ formalDate: '2024-05-12' });
      const component = createComponent({ notice });

      expect(getInputDateComponent(component).props()).toMatchObject({
        value: '2024-05-12',
      });
    });

    test('should display InputGrounds with options and values', () => {
      const groundOptions = [
        { citation: 'mocked-citation', label: 'mocked-label' },
      ];
      const notice = createMockedNotice({ grounds: ['mocked-ground-1'] });
      const component = createComponent({ notice, groundOptions });

      expect(getInputGroundsComponent(component).props()).toMatchObject({
        minLength: 1,
        options: groundOptions,
        values: ['mocked-ground-1'],
      });
    });

    test('should display InputTextarea for explanation with value from notice', () => {
      const notice = createMockedNotice({ explanation: 'mocked-explanation' });
      const component = createComponent({ notice });

      expect(getInputTextareaComponent(component).props()).toMatchObject({
        value: 'mocked-explanation',
        name: 'explanation',
        label: 'Toelichting',
      });
    });
  });

  describe('save button text', () => {
    test('should show "toevoegen" text when isEditMode is false', () => {
      const component = createComponent({ isEditMode: false });
      expect(getSaveButton(component)?.text()).toBe(
        'Opslaan en mededeling toevoegen',
      );
    });

    test('should show "bijwerken" text when isEditMode is true', () => {
      const component = createComponent({ isEditMode: true });
      expect(getSaveButton(component)?.text()).toBe(
        'Opslaan en mededeling bijwerken',
      );
    });
  });

  describe('delete button', () => {
    test('should not be rendered when isEditMode is false', () => {
      const component = createComponent({ isEditMode: false });
      expect(getDeleteButton(component)).toBeUndefined();
    });

    test('should be rendered when isEditMode is true', () => {
      const component = createComponent({ isEditMode: true });
      expect(getDeleteButton(component)).toBeDefined();
    });
  });

  describe('cancel button', () => {
    test('should emit "cancel" event when clicked', async () => {
      const component = createComponent();
      await getCancelButton(component)?.trigger('click');
      expect(component.emitted('cancel')).toBeTruthy();
    });

    test('should clear submit error when clicked', async () => {
      const component = createComponent();
      await getFormComponent(component).vm.$emit('submitError');
      expect(getSaveFailedAlert(component).exists()).toBe(true);

      await getCancelButton(component)?.trigger('click');
      expect(getSaveFailedAlert(component).exists()).toBe(false);
    });
  });

  describe('submit error alert', () => {
    test('should be shown after form emits submitError', async () => {
      const component = createComponent();
      expect(getSaveFailedAlert(component).exists()).toBe(false);

      await getFormComponent(component).vm.$emit('submitError');
      expect(getSaveFailedAlert(component).exists()).toBe(true);
    });

    test('should be hidden after cancel', async () => {
      const component = createComponent();
      await getFormComponent(component).vm.$emit('submitError');
      expect(getSaveFailedAlert(component).exists()).toBe(true);

      await getCancelButton(component)?.trigger('click');
      expect(getSaveFailedAlert(component).exists()).toBe(false);
    });
  });

  describe('submit success', () => {
    test('should emit "saved" event when form emits submitSuccess', async () => {
      const savedNotice = createMockedNotice({ id: 'saved-id' });
      const component = createComponent();

      await getFormComponent(component).vm.$emit('submitSuccess', savedNotice);
      expect(component.emitted('saved')).toBeTruthy();
      expect(component.emitted('saved')?.[0]).toEqual([savedNotice]);
    });
  });

  describe('form submission', () => {
    test('should make a POST request to endpoint in create mode', async () => {
      createComponent();

      const formValue = { documentName: 'test', formalDate: '2024-01-01' };
      await formSubmitFunction(formValue, {});

      expect(globalThis.fetch).toHaveBeenCalledWith(
        'mocked-endpoint',
        expect.objectContaining({
          body: JSON.stringify(formValue),
          headers: {
            'Content-Type': 'application/json',
            accept: 'application/json',
          },
          method: 'POST',
        }),
      );
    });

    test('should make a PUT request to endpoint in edit mode', async () => {
      const notice = createMockedNotice({ id: 'test-id' });
      createComponent({ notice, isEditMode: true });

      const formValue = { documentName: 'updated' };
      await formSubmitFunction(formValue, {});

      expect(globalThis.fetch).toHaveBeenCalledWith(
        'mocked-endpoint',
        expect.objectContaining({
          body: JSON.stringify(formValue),
          headers: {
            'Content-Type': 'application/json',
            accept: 'application/json',
          },
          method: 'PUT',
        }),
      );
    });
  });

  describe('when notice prop changes', () => {
    test('should update field values', async () => {
      const initialNotice = createMockedNotice({
        documentName: 'initial-name',
      });
      const component = createComponent({ notice: initialNotice });

      expect(getInputTextComponent(component).props('value')).toBe(
        'initial-name',
      );

      const updatedNotice = createMockedNotice({
        documentName: 'updated-name',
      });
      await component.setProps({ notice: updatedNotice });
      await nextTick();

      expect(getInputTextComponent(component).props('value')).toBe(
        'updated-name',
      );
    });

    test('should reset form state when notice changes', async () => {
      const notice1 = createMockedNotice();
      const component = createComponent({ notice: notice1 });

      await getFormComponent(component).vm.$emit('submitError');
      expect(getSaveFailedAlert(component).exists()).toBe(true);

      const notice2 = createMockedNotice({ id: 'different-id' });
      await component.setProps({ notice: notice2 });
      await nextTick();

      expect(getSaveFailedAlert(component).exists()).toBe(false);
    });

    test('should clear errors when notice changes', async () => {
      const component = createComponent();
      await getFormComponent(component).vm.$emit('submitError');
      expect(getSaveFailedAlert(component).exists()).toBe(true);

      const newNotice = createMockedNotice({ id: 'new-id' });
      await component.setProps({ notice: newNotice });

      expect(getSaveFailedAlert(component).exists()).toBe(false);
    });
  });
});
