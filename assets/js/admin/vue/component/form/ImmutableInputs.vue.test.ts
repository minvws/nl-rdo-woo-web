import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import ImmutableInputs from './ImmutableInputs.vue';

describe('The "<ImmutableValues />" component', () => {
  const createComponent = () => {
    return mount(ImmutableInputs, {
      props: {
        name: 'mocked-name',
        values: ['mocked-immutable-value-1', 'mocked-immutable-value-2'],
      },
    });
  };

  test('should display each provided value in a hidden input field', () => {
    const inputFields = createComponent().findAll('input');

    expect(inputFields.length).toBe(2);
    expect(inputFields.at(0)?.attributes()).toMatchObject({
      hidden: '',
      name: 'mocked-name[0]',
      value: 'mocked-immutable-value-1',
    });
    expect(inputFields.at(1)?.attributes()).toMatchObject({
      hidden: '',
      name: 'mocked-name[1]',
      value: 'mocked-immutable-value-2',
    });
  });
});
