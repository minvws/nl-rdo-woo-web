import MultiSelectController from '@admin-fe/controllers/MultiSelectController.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "MultiSelectController" component', () => {
  const mockedOptions = ['mocked_option-1', 'mocked_option-2'];
  const mockedSubmitErrors = ['mocked_submit-error'];
  const mockedValues = ['mocked_value-1'];

  const createComponent = () => mount(MultiSelectController, {
    props: {
      buttonText: 'mocked_button_text',
      buttonTextMultiple: 'mocked_button_text_multiple',
      helpText: 'mocked_help_text',
      label: 'mocked_label',
      legend: 'mocked_legend',
      minLength: 100,
      maxLength: 101,
      name: 'mocked_name',
      options: mockedOptions,
      submitErrors: mockedSubmitErrors,
      values: mockedValues,
    },
    shallow: true,
  });

  const getMultiSelectComponent = () => createComponent().findComponent({ name: 'MultiSelect' });

  test('should display a <MultiSelect /> component', async () => {
    const multiSelectComponent = getMultiSelectComponent();

    expect(multiSelectComponent).toBeTruthy();
    expect(multiSelectComponent.props('buttonText')).toEqual('mocked_button_text');
    expect(multiSelectComponent.props('buttonTextMultiple')).toEqual('mocked_button_text_multiple');
    expect(multiSelectComponent.props('helpText')).toEqual('mocked_help_text');
    expect(multiSelectComponent.props('label')).toEqual('mocked_label');
    expect(multiSelectComponent.props('legend')).toEqual('mocked_legend');
    expect(multiSelectComponent.props('minLength')).toEqual(100);
    expect(multiSelectComponent.props('maxLength')).toEqual(101);
    expect(multiSelectComponent.props('name')).toEqual('mocked_name');
    expect(multiSelectComponent.props('options')).toEqual(mockedOptions);
    expect(multiSelectComponent.props('submitErrors')).toEqual(mockedSubmitErrors);
    expect(multiSelectComponent.props('values')).toEqual(mockedValues);
  });
});
