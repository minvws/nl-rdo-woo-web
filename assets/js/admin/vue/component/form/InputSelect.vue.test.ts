import type { Optgroup, SelectOptions } from '@admin-fe/form/interface';
import { mount, type VueWrapper } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import InputSelect from './InputSelect.vue';

const hasVisibleErrors = ref(false);

vi.mock('@admin-fe/composables', () => ({
  useInputStore: vi.fn().mockReturnValue({
    get hasVisibleErrors() {
      return hasVisibleErrors.value;
    },
  }),
  useInputAriaDescribedBy: vi.fn().mockReturnValue('mocked-aria-describedby'),
}));

describe('the <InputSelect /> component', () => {
  interface Options {
    helpText?: string;
    options?: SelectOptions;
    optgroups?: Optgroup[];
  }

  const createComponent = (options: Partial<Options> = {}) =>
    mount(InputSelect, {
      props: {
        helpText: options.helpText,
        label: 'Mocked label',
        name: 'mocked_name',
        options: options.options ?? [],
        optgroups: options.optgroups ?? [],
        validators: [],
        value: 'mocked_value',
      },
      global: {
        provide: {
          form: {
            addInput: vi.fn(),
          },
        },
        renderStubDefaultSlot: true,
      },
      shallow: true,
    });

  const getFormHelpComponent = (wrapper: VueWrapper) =>
    wrapper.findComponent({ name: 'FormHelp' });

  const getFormLabelComponent = (wrapper: VueWrapper) =>
    wrapper.findComponent({ name: 'FormLabel' });

  const getInputErrorsComponent = (wrapper: VueWrapper) =>
    wrapper.findComponent({ name: 'InputErrors' });

  const getErrorMessagesComponent = (wrapper: VueWrapper) =>
    wrapper.findComponent({ name: 'ErrorMessages' });

  const getSelectElement = (wrapper: VueWrapper) => wrapper.find('select');

  beforeEach(() => {
    hasVisibleErrors.value = false;
  });

  it('should render the provided label which points to the upload area', () => {
    const component = createComponent();

    expect(getFormLabelComponent(component).props('for')).toBe(
      getSelectElement(component).attributes('id'),
    );
  });

  it('should render the provided help text', () => {
    expect(getFormHelpComponent(createComponent()).exists()).toBe(false);

    expect(
      getFormHelpComponent(
        createComponent({ helpText: 'Mocked help text' }),
      ).text(),
    ).toBe('Mocked help text');
  });

  it('should render the input errors and error messages if they should be visible', () => {
    let component = createComponent();
    expect(getInputErrorsComponent(component).exists()).toBe(false);
    expect(getErrorMessagesComponent(component).exists()).toBe(false);

    hasVisibleErrors.value = true;
    component = createComponent();
    expect(getInputErrorsComponent(component).exists()).toBe(true);
    expect(getErrorMessagesComponent(component).exists()).toBe(true);
  });

  it('should render the select element with the provided options', () => {
    const component = createComponent({
      options: [
        { label: 'Mocked option 1', value: 'mocked_value_1' },
        { label: 'Mocked option 2', value: 'mocked_value_2' },
      ],
    });
    const options = getSelectElement(component).findAll('option');
    expect(options.length).toBe(3);
    expect(options[0].text()).toBe('Kies een optie');
    expect(options[1].text()).toBe('Mocked option 1');
    expect(options[1].attributes('value')).toBe('mocked_value_1');
    expect(options[2].text()).toBe('Mocked option 2');
    expect(options[2].attributes('value')).toBe('mocked_value_2');
  });

  it('should render the select element with the provided optgroups', () => {
    const component = createComponent({
      optgroups: [
        {
          label: 'Mocked optgroup 1',
          options: [
            { label: 'Mocked option 1', value: 'mocked_value_1' },
            { label: 'Mocked option 2', value: 'mocked_value_2' },
          ],
        },
      ],
    });
    const optGroupOptions = getSelectElement(component)
      .findAll('optgroup')[0]
      .findAll('option');
    expect(optGroupOptions.length).toBe(2);
    expect(optGroupOptions[0].text()).toBe('Mocked option 1');
    expect(optGroupOptions[0].attributes('value')).toBe('mocked_value_1');
    expect(optGroupOptions[1].text()).toBe('Mocked option 2');
    expect(optGroupOptions[1].attributes('value')).toBe('mocked_value_2');
  });
});
