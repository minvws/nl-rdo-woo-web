import MultiInput from '@admin-fe/component/form/MultiInput.vue';
import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "MultiInput" component', () => {
  const mockedOptions = [
    'mocked_option-1',
    'mocked_option-2',
    'mocked_option-3',
  ];
  const mockedErrors = ['mocked_submit-error'];
  const mockedValues = ['mocked_value-1', 'mocked_value-2'];

  interface Options {
    buttonTextMultiple: string;
    helpText: string;
    maxLength: number;
    minLength: number;
    values: string[];
  }

  const createComponent = (options: Partial<Options> = {}) => {
    const {
      buttonTextMultiple,
      helpText,
      maxLength,
      minLength = 1,
      values = mockedValues,
    } = options;

    return mount(MultiInput, {
      props: {
        buttonText: 'mocked_button_text',
        buttonTextMultiple,
        errors: mockedErrors,
        helpText,
        isInvalid: false,
        legend: 'mocked_legend',
        minLength,
        maxLength,
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

    test('should not be visible when no more fields can be added', async () => {
      const component = createComponent({ maxLength: 2 });

      expect(getAddButton(component).exists()).toBe(false);
    });

    test('should contain the multi button text when there are already input fields', async () => {
      const component = createComponent({
        buttonTextMultiple: 'mocked_button_multiple_text',
      });

      expect(getAddButton(component).text()).toContain(
        'mocked_button_multiple_text',
      );
    });

    test('should contain the single button text when there are already input fields and no multi button text is provided', async () => {
      const component = createComponent();

      expect(getAddButton(component).text()).toContain('mocked_button_text');
    });

    test('should contain the single button text when there are no input fields', async () => {
      const component = createComponent({ minLength: 0, values: [] });

      expect(getAddButton(component).text()).toContain('mocked_button_text');
    });
  });

  describe('when deleting an item', () => {
    test('should remove the item with the provided id from the list of values', async () => {
      const component = createComponent();

      const lastEmittedUpdate = getLastEmittedUpdate(component);
      expect(lastEmittedUpdate.length).toEqual(2);

      const { id } = lastEmittedUpdate[0] as any;
      await (component.vm as any).deleteItem(id);
      expect(getLastEmittedUpdate(component).length).toEqual(1);
    });
  });

  describe('when updateing an item', () => {
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
