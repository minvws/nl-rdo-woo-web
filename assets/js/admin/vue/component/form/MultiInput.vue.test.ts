import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import MultiInput from './MultiInput.vue';

describe('The "MultiInput" component', () => {
  const mockedOptions = [
    'mocked_option-1',
    'mocked_option-2',
    'mocked_option-3',
  ];
  const mockedErrors = ['mocked_submit-error'];
  const mockedValues = ['mocked_value-1', 'mocked_value-2'];
  const mockedImmutableValues = [
    'mocked_immutable_value-1',
    'mocked_immutable_value-2',
  ];

  interface Options {
    buttonTextMultiple: string;
    e2eName: string;
    helpText: string;
    immutableValues: string[];
    maxLength: number;
    minLength: number;
    values: string[];
  }

  const createComponent = (options: Partial<Options> = {}) => {
    const {
      buttonTextMultiple,
      e2eName,
      helpText,
      immutableValues = [],
      maxLength,
      minLength = 1,
      values = mockedValues,
    } = options;

    return mount(MultiInput, {
      props: {
        buttonText: 'mocked_button_text',
        buttonTextMultiple,
        e2eName,
        errors: mockedErrors,
        helpText,
        immutableValues,
        isInvalid: false,
        legend: 'mocked_legend',
        minLength,
        maxLength,
        name: 'mocked_name',
        options: mockedOptions,
        values,
      },
      shallow: true,
      global: {
        stubs: {
          FormHelp: false,
        },
      },
      slots: {
        default: 'This is the provided content',
      },
    });
  };

  const getAddButton = (component: VueWrapper) => component.find('button');
  const getLastEmittedUpdate = (component: VueWrapper) =>
    ((component.emitted().update as unknown[][][]).at(-1) ?? [])[0];
  const getFormHelpcomponent = (component: VueWrapper) =>
    component.findComponent({ name: 'FormHelp' });
  const getImmutableInputsComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'ImmutableInputs' });
  const getImmutableValuesComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'ImmutableValues' });

  test('should render a fieldset with the provided legend', async () => {
    expect(createComponent().find('fieldset legend').text()).toContain(
      'mocked_legend',
    );
  });

  test('should render the provided help text', async () => {
    expect(getFormHelpcomponent(createComponent()).exists()).toBe(false);
    expect(
      getFormHelpcomponent(
        createComponent({ helpText: 'mocked_help_text' }),
      ).html(),
    ).toContain('mocked_help_text');
  });

  test('should render the provided error messages', async () => {
    expect(
      createComponent()
        .findComponent({ name: 'ErrorMessages' })
        .props('messages'),
    ).toEqual(mockedErrors);
  });

  test('should render the provided content', async () => {
    expect(createComponent().text()).toContain('This is the provided content');
  });

  test('should display the immutable values', async () => {
    const component = createComponent({
      immutableValues: mockedImmutableValues,
    });
    expect(getImmutableValuesComponent(component).props('values')).toEqual(
      mockedImmutableValues,
    );
  });

  test('should add hidden input fields for the immutable values', async () => {
    const component = createComponent({
      immutableValues: mockedImmutableValues,
    });
    expect(getImmutableInputsComponent(component).props()).toMatchObject({
      name: 'mocked_name',
      values: mockedImmutableValues,
    });
  });

  describe('when no values are provided', () => {
    test('should render a number of input fields equal to the provided value of "minLength"', async () => {
      const component = createComponent();

      await (component as any).setProps({ values: [] });
      expect(getLastEmittedUpdate(component).length).toEqual(1);
    });
  });

  describe('the add button', () => {
    test('should add an input field when clicking it', async () => {
      const component = createComponent();

      await getAddButton(component).trigger('click');
      expect(getLastEmittedUpdate(component).length).toEqual(3);
    });

    test('should have an e2e name based on the provided e2e name', async () => {
      const component = createComponent({ e2eName: 'mocked_e2e_name' });

      expect(getAddButton(component).attributes('data-e2e-name')).toEqual(
        'mocked_e2e_name-button',
      );
    });

    test('should not be visible when no more fields can be added', async () => {
      const component = createComponent({ maxLength: 3 });

      expect(getAddButton(component).exists()).toBe(true);

      await component.setProps({
        immutableValues: ['mocked_immutable_value-1'],
      });
      expect(getAddButton(component).exists()).toBe(false);
    });

    test('should contain the multi button text when there are already input fields or immutable values', async () => {
      const component = createComponent({
        buttonTextMultiple: 'mocked_button_multiple_text',
        immutableValues: [],
        minLength: 0,
        values: [],
      });

      expect(getAddButton(component).text()).toContain('mocked_button_text');

      await component.setProps({
        immutableValues: ['mocked_immutable_value-1'],
      });
      expect(getAddButton(component).text()).toContain(
        'mocked_button_multiple_text',
      );

      await component.setProps({
        immutableValues: [],
        values: ['mocked_value-1'],
      });
      expect(getAddButton(component).text()).toContain(
        'mocked_button_multiple_text',
      );

      await component.setProps({
        immutableValues: [],
        values: [],
      });
      expect(getAddButton(component).text()).toContain('mocked_button_text');
    });

    test('should contain the single button text when there are already input fields and no multi button text is provided', async () => {
      const component = createComponent();

      expect(getAddButton(component).text()).toContain('mocked_button_text');
    });

    test('should contain the single button text when there are no input fields', async () => {
      const component = createComponent({
        buttonTextMultiple: 'mocked_button_multiple_text',
        minLength: 0,
        values: [],
      });

      expect(getAddButton(component).text()).toContain('mocked_button_text');
    });
  });

  describe('deleting an item', () => {
    test('should only be possible if the number of items plus the number of immutable values is greater than the min length', async () => {
      const component = createComponent();
      const vm = component.vm as any;

      expect(vm.canDeleteItem).toBe(true);

      await component.setProps({
        immutableValues: ['mocked_immutable_value-1'],
        minLength: 3,
      });
      expect(vm.canDeleteItem).toBe(false);
    });

    test('should remove the item with the provided id from the list of values', async () => {
      const component = createComponent();

      const lastEmittedUpdate = getLastEmittedUpdate(component);
      expect(lastEmittedUpdate.length).toEqual(2);

      const { id } = lastEmittedUpdate[0] as any;
      await (component.vm as any).deleteItem(id);
      expect(getLastEmittedUpdate(component).length).toEqual(1);
    });
  });

  describe('when updating an item', () => {
    test('should update the item with the provided id with the provided value', async () => {
      const component = createComponent();

      const firstItem = getLastEmittedUpdate(component)[0] as any;
      expect(firstItem.value).toEqual('mocked_value-1');

      await (component.vm as any).updateItem('new_mocked_value', firstItem.id);
      expect((getLastEmittedUpdate(component)[0] as any).value).toEqual(
        'new_mocked_value',
      );
    });
  });
});
