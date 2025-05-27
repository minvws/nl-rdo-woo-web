import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import ImmutableValues from './ImmutableValues.vue';

describe('The "<ImmutableValues />" component', () => {
  const createComponent = (values: string[]) => {
    return mount(ImmutableValues, {
      props: {
        values,
      },
    });
  };

  const getListItems = (component: VueWrapper) => component.findAll('li');
  const getParagraphElement = (component: VueWrapper) => component.find('p');

  test('should display the immutable values in a list when there is more than one of them', () => {
    const component = createComponent([
      'mocked-immutable-value-1',
      'mocked-immutable-value-2',
    ]);

    const listItems = getListItems(component);
    expect(listItems.length).toBe(2);
    expect(listItems.at(0)?.text()).toBe('mocked-immutable-value-1');
    expect(listItems.at(1)?.text()).toBe('mocked-immutable-value-2');

    expect(getParagraphElement(component).exists()).toBe(false);
  });

  test('should display the immutable values in a paragraph when there is only one of them', () => {
    const component = createComponent(['mocked-immutable-value-1']);

    expect(getListItems(component).length).toBe(0);
    expect(getParagraphElement(component).text()).toBe(
      'mocked-immutable-value-1',
    );
  });

  test('should display nothing when there are no immutable values', () => {
    expect(createComponent([]).text()).toBe('');
  });
});
