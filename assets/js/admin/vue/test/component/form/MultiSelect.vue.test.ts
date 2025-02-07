import MultiSelect from '@admin-fe/component/form/MultiSelect.vue';
import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "MultiSelect" component', () => {
  const mockedOptions = ['mocked_option-1', 'mocked_option-2'];
  const mockedSubmitErrors = ['mocked_submit-error'];
  const mockedValues = ['mocked_value-1', 'mocked_value-2'];

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
      },
    });

    const multiInputComponent = getMultiInputComponent(component);
    await multiInputComponent.vm.$emit(
      'update',
      mockedValues.map((value, index) => ({ id: index, value })),
    );
    return component;
  };

  const getMultiInputComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'MultiInput' });
  const getRemovableSelectComponents = (component: VueWrapper) =>
    component.findAllComponents({ name: 'RemovableSelect' });

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
    expect(multiInputComponent.props('options')).toEqual(mockedOptions);
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
});
