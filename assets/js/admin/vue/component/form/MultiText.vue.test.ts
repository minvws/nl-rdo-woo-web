import { VueWrapper, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import { defineComponent } from 'vue';
import MultiText from './MultiText.vue';

const mockedMultiInputStore = {
  addInputStore: vi.fn(),
  removeInputStore: vi.fn(),
  makeDirty: vi.fn(),
};

vi.mock('@admin-fe/composables', () => ({
  useMultiInputStore: () => mockedMultiInputStore,
}));

describe('The "<MultiText />" component', () => {
  const mockedDeleteItem = vi.fn();
  const mockedUpdateItem = vi.fn();
  const mockedValues = ['mocked_value-1', 'mocked_value-2'];

  const MultiInputStub = defineComponent({
    name: 'MultiInput',
    props: {
      buttonText: String,
      buttonTextMultiple: String,
      e2eName: String,
      errors: Array,
      helpText: String,
      immutableValues: Array,
      isInvalid: Boolean,
      legend: String,
      minLength: Number,
      maxLength: Number,
      name: String,
      options: Array,
      values: Array,
    },
    setup() {
      return {
        deleteItem: mockedDeleteItem,
        updateItem: mockedUpdateItem,
      };
    },
    emits: ['update'],
    template: '<slot />',
  });
  const createComponent = async () => {
    const component = mount(MultiText, {
      props: {
        buttonText: 'mocked_button_text',
        buttonTextMultiple: 'mocked_button_text_multiple',
        e2eName: 'mocked_e2e_name',
        helpText: 'mocked_help_text',
        immutableValues: ['mocked-immutale-value-1'],
        label: 'mocked_label',
        legend: 'mocked_legend',
        minLength: 1,
        maxLength: 10,
        name: 'mocked_name',
        submitErrors: ['mocked_submit_error'],
        values: mockedValues,
      },
      shallow: true,
      global: {
        provide: {
          form: {
            addInput: () => ({}),
          },
        },
        stubs: {
          MultiInput: MultiInputStub,
        },
        renderStubDefaultSlot: true,
      },
    });

    await getMultiInputComponent(component).vm.$emit(
      'update',
      mockedValues.map((value, index) => ({ id: index, value })),
    );
    return component;
  };

  const getMultiInputComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'MultiInput' });

  const getRemovableInputTextComponents = (component: VueWrapper) =>
    component.findAllComponents({ name: 'RemovableInputText' });

  test('should display a <MultiInput /> component', async () => {
    const multiInputComponent = getMultiInputComponent(await createComponent());

    expect(multiInputComponent.props()).toMatchObject({
      buttonText: 'mocked_button_text',
      buttonTextMultiple: 'mocked_button_text_multiple',
      e2eName: 'mocked_e2e_name',
      helpText: 'mocked_help_text',
      immutableValues: ['mocked-immutale-value-1'],
      legend: 'mocked_legend',
      minLength: 1,
      maxLength: 10,
      values: ['mocked_value-1', 'mocked_value-2'],
    });
  });

  const triggerRemovableInputTextUpdate = async (
    component: VueWrapper,
    eventName: string,
    value: string,
  ) => {
    await getRemovableInputTextComponents(component)[0].vm.$emit(
      eventName,
      value,
    );
  };

  beforeEach(() => {
    mockedDeleteItem.mockClear();
    mockedUpdateItem.mockClear();
  });

  test('should display a <RemovableInputText /> component for each of the provided values', async () => {
    const component = await createComponent();

    const removableInputTextComponents =
      getRemovableInputTextComponents(component);

    expect(removableInputTextComponents.length).toBe(2);
    expect(removableInputTextComponents[0].props()).toMatchObject({
      forbiddenValues: ['mocked_value-2', 'mocked-immutale-value-1'],
      label: 'mocked_label 2',
      name: 'mocked_name[1]',
      value: mockedValues[0],
    });
  });

  test('should update the items when the <RemovableInputText /> component emits an update event', async () => {
    const component = await createComponent();

    await triggerRemovableInputTextUpdate(
      component,
      'update',
      'mocked_updated_value',
    );

    expect(mockedUpdateItem).toHaveBeenCalledWith('mocked_updated_value', 0);
  });

  test('should delete an item when the <RemovableInputText /> component emits a delete event', async () => {
    const component = await createComponent();

    expect(mockedDeleteItem).not.toHaveBeenCalled();
    expect(mockedMultiInputStore.removeInputStore).not.toHaveBeenCalled();

    await triggerRemovableInputTextUpdate(
      component,
      'delete',
      'mocked_input_store',
    );

    expect(mockedMultiInputStore.removeInputStore).toHaveBeenNthCalledWith(
      1,
      'mocked_input_store',
    );
  });

  test('should add the input store when the <RemovableInputText /> component is mounted', async () => {
    const component = await createComponent();

    expect(mockedMultiInputStore.addInputStore).not.toHaveBeenCalled();

    await triggerRemovableInputTextUpdate(
      component,
      'mounted',
      'mocked_input_store',
    );

    expect(mockedMultiInputStore.addInputStore).toHaveBeenCalledWith(
      'mocked_input_store',
    );
  });
});
