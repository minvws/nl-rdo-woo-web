import RemovableSelect from '@admin-fe/component/form/RemovableSelect.vue';
import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "RemovableSelect" component', () => {
  const mockedErrors = ['mocked-error'];
  const mockedForbiddenValues = ['mocked-forbidden-value'];
  const mockedOptions = ['mocked_value-1', 'mocked_value-2'];
  const mockedOptGroups = [{ label: 'mocked_optgroup_label', options: mockedOptions }];

  interface Properties {
    autoFocus: boolean;
    forbiddenValues: string[];
    options: string[] | { label: string, value: string }[];
    value: string;
  }

  const createComponent = (props: Partial<Properties> = {}) => {
    const { autoFocus = false, forbiddenValues = mockedForbiddenValues, options = mockedOptions, value = 'mocked_value-1' } = props;

    return mount(RemovableSelect, {
      props: {
        autoFocus,
        canDelete: true,
        emptyLabel: 'Mocked emtpy label',
        errors: mockedErrors,
        forbiddenValues,
        label: 'mocked label',
        name: 'mocked_name',
        options,
        optGroups: mockedOptGroups,
        value,
      },
    });
  };

  const getRemovableInputComponent = (component: VueWrapper) => component.findComponent({ name: 'RemovableInput' });
  const getDropdownElement = (component: VueWrapper) => component.find('select').element as HTMLSelectElement;

  test('should display a <RemovableInput /> component', async () => {
    const removableInputComponent = getRemovableInputComponent(createComponent(
      {
        autoFocus: true,
        options: [{ label: 'mocked_label', value: 'mocked_value' }],
        forbiddenValues: [],
      },
    ));

    expect(removableInputComponent).toBeTruthy();
    expect(removableInputComponent.props('canDelete')).toEqual(true);
    expect(removableInputComponent.props('label')).toEqual('mocked label');
  });

  test('should display a dropdown with an empty option plus the provided options', async () => {
    const component = createComponent();

    const options = component.findAll('select > option');
    expect(options.length).toBe(1 + mockedOptions.length);
    expect(options[0].text()).toBe('Mocked emtpy label');
    expect(options[1].attributes('value')).toBe(mockedOptions[0]);
  });

  test('should display a dropdown with the provided opt groups', async () => {
    const component = createComponent({ value: '' });

    const optGroups = component.findAll('optgroup');
    expect(optGroups.length).toBe(mockedOptGroups.length);
    expect(optGroups[0].attributes('label')).toBe('mocked_optgroup_label');
    expect(component.findAll('optgroup option').length).toBe(mockedOptGroups[0].options.length);
  });

  describe('the default value', () => {
    test('should equal the provided value', async () => {
      const component = createComponent();

      expect(getDropdownElement(component).value).toBe('mocked_value-1');
    });

    test('should equal the first provided option is there is only one', async () => {
      const component = createComponent({ value: '', options: ['some_mocked_value'] });

      expect(getDropdownElement(component).value).toBe('some_mocked_value');
    });
  });

  test('should update the value when the prop "value" changes', async () => {
    const component = createComponent();

    await (component as any).setProps({ value: 'mocked_value-2' });

    expect(getDropdownElement(component).value).toBe('mocked_value-2');
  });

  test('should emit an event when the element gets removed', async () => {
    const component = createComponent();
    const removableInputComponent = getRemovableInputComponent(component);

    expect(component.emitted().delete).toBeFalsy();

    await removableInputComponent.vm.$emit('delete');
    expect(component.emitted().delete).toBeTruthy();
  });

  test('should emit an event when the dropdown element changes its value', async () => {
    const component = createComponent();

    expect(component.emitted().update).toBeFalsy();

    getDropdownElement(component).dispatchEvent(new Event('change'));
    expect(component.emitted().update).toBeTruthy();
  });

  describe('when the current value is empty and there is only one option', () => {
    test('should make the value equal to the only option', async () => {
      const component = createComponent({ value: '' });

      expect(getDropdownElement(component).value).toBe('');

      await (component as any).setProps({ options: [...mockedOptions, 'another_mocked_option'] });
      expect(getDropdownElement(component).value).toBe('');

      await (component as any).setProps({ options: ['mocked_option'] });
      expect(getDropdownElement(component).value).toBe('mocked_option');

      await (component as any).setProps({ options: [...mockedOptions, 'mocked_option'] });
      expect(getDropdownElement(component).value).toBe('mocked_option');
    });
  });
});
