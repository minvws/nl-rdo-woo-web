import { mount, VueWrapper } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import InputTextarea from './InputTextarea.vue';

vi.mock('@js/utils', () => ({
  uniqueId: () => 'mocked-id',
}));

let mockedInputStore: any;

vi.mock('@admin-fe/composables', () => ({
  useInputAriaDescribedBy: () => 'mocked-aria-described-by',
  useInputStore: () => mockedInputStore,
}));

describe('The "<InputTextarea />" component', () => {
  interface Options {
    label?: string;
    required?: boolean;
    value?: string;
  }

  beforeEach(() => {
    mockedInputStore = {
      errors: [],
      hasVisibleErrors: false,
      submitValidationErrors: [],
      markAsTouched: vi.fn(),
    };
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  const createComponent = (options: Partial<Options> = {}) =>
    mount(InputTextarea, {
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

  const getErrorMessagesComponent = (wrapper: VueWrapper) =>
    wrapper.findComponent({ name: 'ErrorMessages' });

  const getTextareaElement = (wrapper: VueWrapper) => wrapper.find('textarea');

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

  test('should display a textarea element', () => {
    const component = createComponent({ required: false });
    expect(getTextareaElement(component).attributes()).toMatchObject({
      'aria-describedby': 'mocked-aria-described-by',
      'aria-invalid': 'false',
      id: 'mocked-id',
      name: 'mocked-name',
      maxlength: '50000',
    });
  });

  test('should display the provided value in the textarea element', async () => {
    const component = createComponent({ value: 'mocked-value' });
    const textareaElement = getTextareaElement(component);

    expect(textareaElement.element.value).toBe('mocked-value');

    await component.setProps({ value: 'mocked-value-2' });
    expect(textareaElement.element.value).toBe('mocked-value-2');
  });

  test('should render ErrorMessages when hasVisibleErrors is true', () => {
    mockedInputStore.hasVisibleErrors = true;
    mockedInputStore.submitValidationErrors = ['mocked-error'];

    const component = createComponent();

    expect(getErrorMessagesComponent(component).exists()).toBe(true);
  });

  test('should call markAsTouched when textarea loses focus', async () => {
    const component = createComponent();

    await getTextareaElement(component).trigger('blur');

    expect(mockedInputStore.markAsTouched).toHaveBeenCalled();
  });
});
