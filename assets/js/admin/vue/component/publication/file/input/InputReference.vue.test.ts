import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import InputReference from './InputReference.vue';

describe('The "InputLanguages" component', () => {
  const createComponent = () =>
    mount(InputReference, {
      props: {
        value: 'mocked-value',
      },
      shallow: true,
    });

  const getInputTextComponent = () =>
    createComponent().findComponent({ name: 'InputText' });

  test('should display a text field with the right properties', () => {
    expect(getInputTextComponent().props()).toMatchObject({
      label: 'Referentienummer bestand',
      name: 'internalReference',
      value: 'mocked-value',
    });
  });
});
