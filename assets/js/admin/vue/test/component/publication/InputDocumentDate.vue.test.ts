import InputDocumentDate from '@admin-fe/component/publication/InputDocumentDate.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "InputDocumentDate" component', () => {
  const createComponent = () =>
    mount(InputDocumentDate, {
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
