import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import InputDate from './InputDate.vue';

describe('The "InputDate" component', () => {
  interface Options {
    label: string;
  }

  const createComponent = (options: Partial<Options> = {}) =>
    mount(InputDate, {
      props: {
        label: options.label,
        value: 'mocked-value',
      },
      shallow: true,
    });

  const getInputTextComponent = (component: VueWrapper = createComponent()) =>
    component.findComponent({ name: 'InputText' });

  test('should display a date field with a help text being "De datering die in het document wordt gebruikt."', () => {
    expect(getInputTextComponent().props('helpText')).toBe(
      'De datering die in het document wordt gebruikt.',
    );
  });

  test('should display a text field with a label being "Formele datum" by default', async () => {
    const component = createComponent();
    expect(getInputTextComponent(component).props('label')).toBe(
      'Formele datum',
    );

    await component.setProps({ label: 'Mocked label' });
    expect(getInputTextComponent(component).props('label')).toBe(
      'Mocked label',
    );
  });

  test('should display a text field which allows the user to pick a date', () => {
    expect(getInputTextComponent().props('type')).toBe('date');
  });
});
