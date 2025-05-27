import { mount, VueWrapper } from '@vue/test-utils';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import MultiSelect from './MultiSelect.vue';
import { defineComponent } from 'vue';

const mockedMultiInputStore = {
  addInputStore: vi.fn(),
  removeInputStore: vi.fn(),
  makeDirty: vi.fn(),
};

vi.mock('@admin-fe/composables', () => ({
  useMultiInputStore: () => mockedMultiInputStore,
}));

describe('The "MultiSelect" component', () => {
  const mockedOptions = [
    { label: 'mocked_option-1', value: 'mocked_option-1' },
    { label: 'mocked_option-2', value: 'mocked_option-2' },
  ];
  const mockedSubmitErrors = ['mocked_submit-error'];
  const mockedValues = ['mocked_value-1', 'mocked_value-2'];

  const mockedDeleteItem = vi.fn();
  const mockedUpdateItem = vi.fn();

  const MultiInputStub = defineComponent({
    name: 'MultiInput',
    props: {
      buttonText: String,
      buttonTextMultiple: String,
      errors: Array,
      helpText: String,
      isInvalid: Boolean,
      legend: String,
      minLength: Number,
      maxLength: Number,
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
    const component = mount(MultiSelect, {
      props: {
        buttonText: 'mocked_button_text',
        buttonTextMultiple: 'mocked_button_text_multiple',
        helpText: 'mocked_help_text',
        label: 'mocked_label',
        legend: 'mocked_legend',
        minLength: 10,
        maxLength: 11,
        name: 'mocked_name',
        options: mockedOptions,
        submitErrors: mockedSubmitErrors,
        values: mockedValues,
      },
      global: {
        provide: {
          form: {
            addInput: () => ({}),
          },
        },
        renderStubDefaultSlot: true,
        stubs: {
          MultiInput: MultiInputStub,
        },
      },
      shallow: true,
    });

    await getMultiInputComponent(component).vm.$emit(
      'update',
      mockedValues.map((value, index) => ({ id: index, value })),
    );
    return component;
  };

  const triggerRemovableSelectUpdate = async (
    component: VueWrapper,
    eventName: string,
    value: string,
  ) => {
    await getRemovableSelectComponents(component)[0].vm.$emit(eventName, value);
  };

  const getMultiInputComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'MultiInput' });
  const getRemovableSelectComponents = (component: VueWrapper) =>
    component.findAllComponents({ name: 'RemovableSelect' });

  beforeEach(() => {
    mockedDeleteItem.mockClear();
    mockedUpdateItem.mockClear();
  });

  test('should display a <MultiInput /> component', async () => {
    const multiInputComponent = getMultiInputComponent(await createComponent());

    expect(multiInputComponent).toBeTruthy();
    expect(multiInputComponent.props('buttonText')).toEqual(
      'mocked_button_text',
    );
    expect(multiInputComponent.props('buttonTextMultiple')).toEqual(
      'mocked_button_text_multiple',
    );
    expect(multiInputComponent.props('helpText')).toEqual('mocked_help_text');
    expect(multiInputComponent.props('legend')).toEqual('mocked_legend');
    expect(multiInputComponent.props('minLength')).toEqual(10);
    expect(multiInputComponent.props('maxLength')).toEqual(11);
    expect(multiInputComponent.props('options')).toEqual(
      mockedOptions.map((option) => option.value),
    );
    expect(multiInputComponent.props('values')).toEqual(mockedValues);
  });

  test('should display a <RemovableSelect /> component for each of the provided values', async () => {
    const component = await createComponent();

    const removableSelectComponents = getRemovableSelectComponents(component);

    expect(removableSelectComponents.length).toBe(2);
    expect(removableSelectComponents[0].props('label')).toEqual(
      'mocked_label 1',
    );
    expect(removableSelectComponents[0].props('options')).toEqual(
      mockedOptions,
    );
    expect(removableSelectComponents[0].props('value')).toEqual(
      mockedValues[0],
    );
  });

  test('should update the items when the <RemovableSelect /> component emits an update event', async () => {
    const component = await createComponent();

    await triggerRemovableSelectUpdate(
      component,
      'update',
      'mocked_updated_value',
    );

    expect(mockedUpdateItem).toHaveBeenCalledWith('mocked_updated_value', 0);
  });

  test('should delete an item when the <RemovableSelect /> component emits a delete event', async () => {
    const component = await createComponent();

    expect(mockedDeleteItem).not.toHaveBeenCalled();
    expect(mockedMultiInputStore.removeInputStore).not.toHaveBeenCalled();

    await triggerRemovableSelectUpdate(
      component,
      'delete',
      'mocked_input_store',
    );

    expect(mockedDeleteItem).toHaveBeenCalledWith(0);
    expect(mockedMultiInputStore.removeInputStore).toHaveBeenCalledWith(
      'mocked_input_store',
    );
  });

  test('should add the input store when the <RemovableSelect /> component is mounted', async () => {
    const component = await createComponent();

    expect(mockedMultiInputStore.addInputStore).not.toHaveBeenCalled();

    await triggerRemovableSelectUpdate(
      component,
      'mounted',
      'mocked_input_store',
    );

    expect(mockedMultiInputStore.addInputStore).toHaveBeenCalledWith(
      'mocked_input_store',
    );
  });
});
