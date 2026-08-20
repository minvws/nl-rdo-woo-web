import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import InputGrounds from './InputGrounds.vue';

describe('The "InputGrounds" component', () => {
  const createComponent = (hasOptions = true) => {
    return mount(InputGrounds, {
      props: {
        minLength: 3,
        options: hasOptions
          ? [{ citation: 'mocked-citation-1', label: 'mocked-label-1' }]
          : [],
        values: ['mocked-value-1', 'mocked-value-2'],
      },
      shallow: true,
    });
  };

  const getMultiSelectComponent = (vueWrapper: VueWrapper) =>
    vueWrapper.findComponent({ name: 'MultiSelect' });

  test('should display a multi select component with the right properties', () => {
    expect(getMultiSelectComponent(createComponent()).props()).toMatchObject({
      buttonText: 'Weigeringsgrond toevoegen',
      buttonTextMultiple: 'Nog een weigeringsgrond toevoegen',
      helpText:
        'Zijn in dit document gegevens gelakt? Kies dan de gebruikte weigeringsgronden.',
      legend: 'Weigeringsgronden',
      label: 'Weigeringsgrond',
      minLength: 3,
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

  test('should display nothing when no options to choose from are provided', () => {
    expect(getMultiSelectComponent(createComponent(false)).exists()).toBe(
      false,
    );
  });
});
