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
  const submitSchema = z.object({});

  const createComponent = () => {
    store = useFormStore(
      () =>
        Promise.resolve({
          ok: true,
          statusText: 'OK',
          json: () => Promise.resolve({}),
        } as Response),
      submitSchema,
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
  const triggerSubmit = (formElement: DOMWrapper<HTMLFormElement>) => {
    formElement.trigger('submit');
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
    test('should tell the store it can display errors', () => {
      const formElement = getFormElement(createComponent());

      expect(store.markAsShouldDisplayErrors).not.toHaveBeenCalled();

      triggerSubmit(formElement);
      expect(store.markAsShouldDisplayErrors).toHaveBeenCalled();
    });

    test('should emit an "pristineSubmit" event when the form is pristine', () => {
      const component = createComponent();
      const formElement = getFormElement(component);

      expect(component.emitted('pristineSubmit')).toBeUndefined();

      triggerSubmit(formElement);
      expect(component.emitted('pristineSubmit')).toBeDefined();
    });

    test('should tell the store to reset submit validation errors', async () => {
      const component = createComponent();
      const formElement = getFormElement(component);

      expect(store.resetSubmitValidationErrors).not.toHaveBeenCalled();

      store.getInputStore('name')?.setValue('Ben');
      await nextTick();
      triggerSubmit(formElement);
      expect(store.resetSubmitValidationErrors).toHaveBeenCalled();
    });

    test('should validate the response', async () => {
      const component = createComponent();
      const formElement = getFormElement(component);

      expect(validateData).not.toHaveBeenCalled();

      store.getInputStore('name')?.setValue('Ben');
      await nextTick();
      triggerSubmit(formElement);
      await flushPromises();
      expect(validateData).toHaveBeenNthCalledWith(1, {}, submitSchema);
    });
  });
});
