import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import InputLanguages from './InputLanguages.vue';

describe('The "InputLanguages" component', () => {
  const createComponent = () =>
    mount(InputLanguages, {
      props: {
        options: [{ label: 'mocked-label-1', value: 'mocked-value-1' }],
        value: 'mocked-value-1',
      },
      shallow: true,
    });

  const getDropdownComponent = () =>
    createComponent().findComponent({ name: 'InputSelect' });

  test('should display a dropdown component with the right properties', () => {
    expect(getDropdownComponent().props()).toMatchObject({
      label: 'Taal van het document',
      name: 'language',
      options: [{ label: 'mocked-label-1', value: 'mocked-value-1' }],
      value: 'mocked-value-1',
    });
  });
});
