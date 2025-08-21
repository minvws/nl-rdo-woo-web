import { VueWrapper, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import { defineComponent } from 'vue';
import MultiCombobox from './MultiCombobox.vue';

const mockedMultiInputStore = {
  addInputStore: vi.fn(),
  removeInputStore: vi.fn(),
  makeDirty: vi.fn(),
};

vi.mock('@admin-fe/composables', () => ({
  useMultiInputStore: () => mockedMultiInputStore,
}));

describe('The "<MultiCombobox />" component', () => {
  const mockedDeleteItem = vi.fn();
  const mockedUpdateItem = vi.fn();
  const mockedValues = ['mocked_option-1', 'mocked_option-2'];

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
    const component = mount(MultiCombobox, {
      props: {
        buttonText: 'mocked_button_text',
        buttonTextMultiple: 'mocked_button_text_multiple',
        helpText: 'mocked_help_text',
        label: 'mocked_label',
        legend: 'mocked_legend',
        maxLength: 4,
        maxValueLength: 33,
        minLength: 1,
        minValueLength: 2,
        name: 'mocked_name',
        options: ['mocked_option-1', 'mocked_option-2', 'mocked_option-3'],
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

  const getComboboxComponents = (component: VueWrapper) =>
    component.findAllComponents({ name: 'Combobox' });

  test('should display a <MultiInput /> component', async () => {
    const multiInputComponent = getMultiInputComponent(await createComponent());

    expect(multiInputComponent.props()).toMatchObject({
      buttonText: 'mocked_button_text',
      buttonTextMultiple: 'mocked_button_text_multiple',
      helpText: 'mocked_help_text',
      legend: 'mocked_legend',
      maxLength: 4,
      minLength: 1,
      options: ['mocked_option-1', 'mocked_option-2', 'mocked_option-3'],
      values: ['mocked_option-1', 'mocked_option-2'],
    });
  });

  const triggerComboboxUpdate = async (
    component: VueWrapper,
    eventName: string,
    value: string,
  ) => {
    await getComboboxComponents(component)[0].vm.$emit(eventName, value);
  };

  beforeEach(() => {
    mockedDeleteItem.mockClear();
    mockedUpdateItem.mockClear();
  });

  test('should display a <Combobox /> component for each of the provided values', async () => {
    const component = await createComponent();

    const comboboxComponents = getComboboxComponents(component);

    expect(comboboxComponents.length).toBe(2);
    expect(comboboxComponents[0].props()).toMatchObject({
      forbiddenValues: ['mocked_option-2'],
      label: 'mocked_label 1',
      maxLength: 33,
      minLength: 2,
      name: 'mocked_name[0]',
      value: mockedValues[0],
    });
    expect(comboboxComponents[1].props()).toMatchObject({
      forbiddenValues: ['mocked_option-1'],
      label: 'mocked_label 2',
      maxLength: 33,
      minLength: 2,
      name: 'mocked_name[1]',
      value: mockedValues[1],
    });
  });

  test('should update the items when the <Combobox /> component emits an update event', async () => {
    const component = await createComponent();

    await triggerComboboxUpdate(component, 'update', 'mocked_updated_value');

    expect(mockedUpdateItem).toHaveBeenCalledWith('mocked_updated_value', 0);
  });

  test('should delete an item when the <Combobox /> component emits a delete event', async () => {
    const component = await createComponent();

    expect(mockedDeleteItem).not.toHaveBeenCalled();
    expect(mockedMultiInputStore.removeInputStore).not.toHaveBeenCalled();

    await triggerComboboxUpdate(component, 'delete', 'mocked_input_store');

    expect(mockedMultiInputStore.removeInputStore).toHaveBeenNthCalledWith(
      1,
      'mocked_input_store',
    );
  });

  test('should add the input store when the <RemovableInputText /> component is mounted', async () => {
    const component = await createComponent();

    expect(mockedMultiInputStore.addInputStore).not.toHaveBeenCalled();

    await triggerComboboxUpdate(component, 'mounted', 'mocked_input_store');

    expect(mockedMultiInputStore.addInputStore).toHaveBeenCalledWith(
      'mocked_input_store',
    );
  });
});
