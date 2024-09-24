import MultiComboboxController from '@admin-fe/controllers/MultiComboboxController.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "MultiComboboxController" component', () => {
  const mockedOptions = ['mocked_option-1', 'mocked_option-2'];
  const mockedSubmitErrors = ['mocked_submit-error'];
  const mockedValues = ['mocked_value-1'];

  const createComponent = () => mount(MultiComboboxController, {
    props: {
      buttonText: 'mocked_button_text',
      buttonTextMultiple: 'mocked_button_text_multiple',
      helpText: 'mocked_help_text',
      label: 'mocked_label',
      legend: 'mocked_legend',
      minLength: 100,
      name: 'mocked_name',
      options: mockedOptions,
      submitErrors: mockedSubmitErrors,
      values: mockedValues,
    },
    shallow: true,
  });

  const getMultiComboboxComponent = () => createComponent().findComponent({ name: 'MultiCombobox' });

  test('should display a <MultiCombobox /> component', async () => {
    const multiComboboxComponent = getMultiComboboxComponent();

    expect(multiComboboxComponent).toBeTruthy();
    expect(multiComboboxComponent.props('buttonText')).toEqual('mocked_button_text');
    expect(multiComboboxComponent.props('buttonTextMultiple')).toEqual('mocked_button_text_multiple');
    expect(multiComboboxComponent.props('helpText')).toEqual('mocked_help_text');
    expect(multiComboboxComponent.props('label')).toEqual('mocked_label');
    expect(multiComboboxComponent.props('legend')).toEqual('mocked_legend');
    expect(multiComboboxComponent.props('minLength')).toEqual(100);
    expect(multiComboboxComponent.props('name')).toEqual('mocked_name');
    expect(multiComboboxComponent.props('options')).toEqual(mockedOptions);
    expect(multiComboboxComponent.props('submitErrors')).toEqual(mockedSubmitErrors);
    expect(multiComboboxComponent.props('values')).toEqual(mockedValues);
  });
});
