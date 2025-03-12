import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import InputDate from './InputDate.vue';

describe('The "InputDate" component', () => {
  const createComponent = () =>
    mount(InputDate, {
      props: {
        value: 'mocked-value',
      },
      shallow: true,
    });

  const getInputTextComponent = () =>
    createComponent().findComponent({ name: 'InputText' });

  test('should display a date field with a help text being "De datering die in het document wordt gebruikt."', () => {
    expect(getInputTextComponent().props('helpText')).toBe(
      'De datering die in het document wordt gebruikt.',
    );
  });

  test('should display a text field with a label being "Formele datum"', () => {
    expect(getInputTextComponent().props('label')).toBe('Formele datum');
  });

  test('should display a text field which allows the user to pick a date', () => {
    expect(getInputTextComponent().props('type')).toBe('date');
  });
});
