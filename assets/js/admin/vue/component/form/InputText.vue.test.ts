import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test, vi } from 'vitest';
import InputText from './InputText.vue';

vi.mock('@js/utils', () => ({
  uniqueId: () => 'mocked-id',
}));

vi.mock('@admin-fe/composables', () => ({
  useInputAriaDescribedBy: () => 'mocked-aria-described-by',
  useInputStore: () => ({
    errors: [],
    hasVisibleErrors: false,
  }),
}));

describe('The "<InputText />  " component', () => {
  interface Options {
    label?: string;
    required?: boolean;
    value?: string;
  }

  const createComponent = (options: Partial<Options> = {}) =>
    mount(InputText, {
      props: {
        label: options.label,
        name: 'mocked-name',
        required: options.required,
        value: options.value,
      },
      global: {
        renderStubDefaultSlot: true,
        provide: {
          form: {
            addInput: vi.fn(),
          },
        },
      },
      shallow: true,
    });

  const getFormLabelComponent = (wrapper: VueWrapper) =>
    wrapper.findComponent({ name: 'FormLabel' });

  const getFormHelpComponent = (wrapper: VueWrapper) =>
    wrapper.findComponent({ name: 'FormHelp' });

  const getInputElement = (wrapper: VueWrapper) => wrapper.find('input');

  test('should render a label element if a label is provided', async () => {
    const component = createComponent();

    expect(getFormLabelComponent(component).exists()).toBeFalsy();

    await component.setProps({ label: 'mocked-label' });

    const formLabelComponent = getFormLabelComponent(component);
    expect(formLabelComponent.text()).toBe('mocked-label');
    expect(formLabelComponent.props('for')).toBe('mocked-id');
  });

  test('should render the help text if it is provided', async () => {
    const component = createComponent();

    expect(getFormHelpComponent(component).exists()).toBeFalsy();

    await component.setProps({ helpText: 'mocked-help-text' });

    const formHelpComponent = getFormHelpComponent(component);
    expect(formHelpComponent.text()).toBe('mocked-help-text');
    expect(formHelpComponent.props('inputId')).toBe('mocked-id');
  });

  test('should display a text field', () => {
    const component = createComponent({ required: false });
    expect(getInputElement(component).attributes()).toMatchObject({
      'aria-describedby': 'mocked-aria-described-by',
      'aria-invalid': 'false',
      id: 'mocked-id',
      name: 'mocked-name',
      type: 'text',
    });
  });

  test('should display the provided value in the input element', async () => {
    const component = createComponent({ value: 'mocked-value' });
    const inputElement = getInputElement(component);

    expect(inputElement.element.value).toBe('mocked-value');

    await component.setProps({ value: 'mocked-value-2' });
    expect(inputElement.element.value).toBe('mocked-value-2');
  });
});
