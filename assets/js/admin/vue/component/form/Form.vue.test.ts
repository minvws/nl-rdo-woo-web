import { validateData } from '@js/admin/utils';
import { DOMWrapper, flushPromises, mount, VueWrapper } from '@vue/test-utils';
import { afterEach, describe, expect, test, vi } from 'vitest';
import { nextTick, ref } from 'vue';
import { z } from 'zod';
import { useFormStore } from '../../composables/form-store';
import { useInputStore } from '../../composables/input-store';
import Form from './Form.vue';

vi.mock('@js/admin/utils', async (importOriginal) => {
  const original = await importOriginal<typeof import('@js/admin/utils')>();
  return {
    ...original,
    validateData: vi.fn(),
  };
});

describe('The "<Form />" component', () => {
  let store: ReturnType<typeof useFormStore>;
  const mockedSubmitSchema = z.object({});

  const createComponent = (statusCode = 200) => {
    store = useFormStore(
      () =>
        Promise.resolve({
          ok: true,
          statusText: 'OK',
          status: statusCode,
          json: () => Promise.resolve({ name: 'John', age: 25 }),
        } as Response),
      mockedSubmitSchema,
    );
    store.addInput(useInputStore('name', 'Name', ref('John')));
    store.addInput(useInputStore('age', 'Age', ref(25)));

    vi.spyOn(store, 'markAsShouldDisplayErrors');
    vi.spyOn(store, 'resetSubmitValidationErrors');

    return mount(Form, {
      props: {
        store,
      },
      slots: {
        default: '<p>Mocked provided content</p>',
      },
    });
  };

  const getFormElement = (component: VueWrapper) => component.find('form');
  const triggerSubmit = async (formElement: DOMWrapper<HTMLFormElement>) => {
    await nextTick();
    formElement.trigger('submit');
    await flushPromises();
  };

  afterEach(() => {
    vi.clearAllMocks();
  });

  test('should render a <form> element with the provided content', () => {
    expect(getFormElement(createComponent()).text()).toBe(
      'Mocked provided content',
    );
  });

  describe('when the form is submitted', () => {
    test('should tell the store it can display errors', async () => {
      const formElement = getFormElement(createComponent());

      expect(store.markAsShouldDisplayErrors).not.toHaveBeenCalled();

      await triggerSubmit(formElement);
      expect(store.markAsShouldDisplayErrors).toHaveBeenCalled();
    });

    test('should emit an "pristineSubmit" event when the form is pristine', async () => {
      const component = createComponent();
      const formElement = getFormElement(component);

      expect(component.emitted('pristineSubmit')).toBeUndefined();

      await triggerSubmit(formElement);
      expect(component.emitted('pristineSubmit')).toBeDefined();
    });

    test('should emit a "submitError" event when validating the response fails', async () => {
      const component = createComponent(300);
      const formElement = getFormElement(component);

      expect(component.emitted('submitError')).toBeUndefined();

      store.getInputStore('name')?.setValue('Ben');
      await triggerSubmit(formElement);
      expect(component.emitted('submitError')).toBeDefined();
    });

    test('should emit a "submitSuccess" event when the response is valid', async () => {
      const component = createComponent();
      const formElement = getFormElement(component);

      expect(component.emitted('submitSuccess')).toBeUndefined();

      store.getInputStore('name')?.setValue('Ben');
      await triggerSubmit(formElement);
      expect(component.emitted('submitSuccess')).toBeDefined();
    });

    test('should tell the store to reset submit validation errors', async () => {
      const component = createComponent();
      const formElement = getFormElement(component);

      expect(store.resetSubmitValidationErrors).not.toHaveBeenCalled();

      store.getInputStore('name')?.setValue('Ben');
      await triggerSubmit(formElement);
      expect(store.resetSubmitValidationErrors).toHaveBeenCalled();
    });

    test('should validate the response', async () => {
      const component = createComponent();
      const formElement = getFormElement(component);

      expect(validateData).not.toHaveBeenCalled();

      store.getInputStore('name')?.setValue('Ben');
      await triggerSubmit(formElement);
      expect(validateData).toHaveBeenNthCalledWith(
        1,
        { name: 'John', age: 25 },
        mockedSubmitSchema,
      );
    });

    test('should inform the store about violations if there are any', async () => {
      vi.mocked(validateData).mockImplementationOnce(() => ({
        violations: [{ propertyPath: 'name', message: 'Name is required' }],
      }));

      const component = createComponent(422);
      const formElement = getFormElement(component);

      vi.spyOn(store, 'addSubmitValidationError');

      expect(store.addSubmitValidationError).not.toHaveBeenCalled();

      store.getInputStore('name')?.setValue('Ben');
      await triggerSubmit(formElement);
      expect(store.addSubmitValidationError).toHaveBeenNthCalledWith(
        1,
        'Name is required',
        'name',
      );
    });
  });
});
