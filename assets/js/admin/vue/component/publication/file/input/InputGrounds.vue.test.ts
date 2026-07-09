import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import InputGrounds from './InputGrounds.vue';

describe('The "InputGrounds" component', () => {
  const createComponent = () =>
    mount(InputGrounds, {
      props: {
        options: [{ citation: 'mocked-citation-1', label: 'mocked-label-1' }],
        values: ['mocked-value-1', 'mocked-value-2'],
      },
      shallow: true,
    });

  const getMultiSelectComponent = () =>
    createComponent().findComponent({ name: 'MultiSelect' });

  test('should display a multi select component with the right properties', () => {
    expect(getMultiSelectComponent().props()).toMatchObject({
      buttonText: 'Weigeringsgrond toevoegen',
      buttonTextMultiple: 'Nog een weigeringsgrond toevoegen',
      helpText:
        'Zijn in dit document gegevens gelakt? Kies dan de gebruikte weigeringsgronden.',
      legend: 'Weigeringsgronden',
      label: 'Weigeringsgrond',
      minLength: 0,
      name: 'grounds',
      options: [
        {
          label: 'mocked-citation-1 mocked-label-1',
          value: 'mocked-citation-1',
        },
      ],
      values: ['mocked-value-1', 'mocked-value-2'],
    });
  });

  test('should pass minLength prop to MultiSelect when provided', () => {
    const component = mount(InputGrounds, {
      props: {
        minLength: 1,
        options: [{ citation: 'mocked-citation-1', label: 'mocked-label-1' }],
        values: [],
      },
      shallow: true,
    });

    expect(
      component.findComponent({ name: 'MultiSelect' }).props('minLength'),
    ).toBe(1);
  });
});
