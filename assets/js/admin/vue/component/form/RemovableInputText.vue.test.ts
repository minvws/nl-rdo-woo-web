import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test, vi } from 'vitest';
import RemovableInputText from './RemovableInputText.vue';
import { useInputStore } from '@admin-fe/composables';

vi.mock('@js/utils', () => ({
  uniqueId: vi.fn(() => 'mocked_id'),
}));

vi.mock('@admin-fe/composables', () => ({
  useInputStore: vi.fn().mockReturnValue({
    isMockedInputStore: true,
    errors: [],
    hasVisibleErrors: false,
    markAsTouched: vi.fn(),
    setValidators: vi.fn(),
  }),
  useInputAriaDescribedBy: vi.fn().mockReturnValue('mocked-aria-describedby'),
}));

describe('The "RemovableInputText" component', () => {
  interface Options {
    autoFocus: boolean;
    e2eName: string;
    forbiddenValues: string[];
    minLength: number;
  }
  const createComponent = (options: Partial<Options> = {}) => {
    return mount(RemovableInputText, {
      props: {
        autoFocus: options.autoFocus ?? false,
        canDelete: true,
        e2eName: options.e2eName,
        forbiddenValues: options.forbiddenValues,
        label: 'mocked label',
        minLength: options.minLength,
        name: 'mocked_name',
        value: 'mocked_value',
      },
      global: {
        renderStubDefaultSlot: true,
      },
      shallow: true,
    });
  };

  const getRemovableInputComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'RemovableInput' });

  const getInputElement = (component: VueWrapper) => component.find('input');

  test('should display a <RemovableInput /> component', async () => {
    const removableInputComponent = getRemovableInputComponent(
      createComponent({ autoFocus: true, minLength: 10 }),
    );

    expect(removableInputComponent).toBeTruthy();
    expect(removableInputComponent.props()).toMatchObject({
      canDelete: true,
      label: 'mocked label',
    });
  });

  test('should display an <input /> element', async () => {
    expect(
      getInputElement(
        createComponent({ e2eName: 'mocked_e2e_name' }),
      ).attributes(),
    ).toMatchObject({
      'aria-describedby': 'mocked-aria-describedby',
      'data-e2e-name': 'mocked_e2e_name-input',
      id: 'mocked_id',
      name: 'mocked_name',
      type: 'text',
    });
  });

  test('should emit a "delete" event when the <RemovableInput /> component emits a "delete" event', async () => {
    const removableInputComponent =
      getRemovableInputComponent(createComponent());

    expect(removableInputComponent.emitted('delete')).toBeFalsy();

    await removableInputComponent.vm.$emit('delete');

    expect(removableInputComponent.emitted('delete')).toBeTruthy();
  });

  test('should emit an event when the input element changes its value', async () => {
    const component = createComponent();

    expect(component.emitted().update).toBeFalsy();

    await getInputElement(component).setValue('mocked_value_2');
    expect(component.emitted().update).toBeTruthy();
  });

  test('should mark the input as touched when the input element is blurred', async () => {
    const component = createComponent();

    expect((useInputStore as any)().markAsTouched).not.toHaveBeenCalled();

    await getInputElement(component).trigger('blur');
    expect((useInputStore as any)().markAsTouched).toHaveBeenCalled();
  });

  test('it should set the validators when the forbidden values change', async () => {
    const component = createComponent();

    await component.setProps({ forbiddenValues: ['Mocked forbidden value 3'] });
    expect((useInputStore as any)().setValidators).toHaveBeenCalledWith(
      expect.arrayContaining([expect.any(Function)]),
    );
  });
});
