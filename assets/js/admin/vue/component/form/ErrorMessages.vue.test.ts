import ErrorMessages from './ErrorMessages.vue';
import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "ErrorMessages" component', () => {
  interface Options {
    id?: string;
    messages?: string[];
  }

  const createComponent = (options: Partial<Options> = {}) => {
    const { id, messages = ['mocked_message_1', 'mocked_message_2'] } = options;

    return mount(ErrorMessages, {
      props: {
        id,
        messages,
      },
      shallow: true,
    });
  };

  const getList = (component: VueWrapper) => component.find('ul');
  const getParagraph = (component: VueWrapper) => component.find('p');

  test('should render an exclamation icon', () => {
    const component = createComponent();
    const iconComponent = component.findComponent({ name: 'Icon' });
    expect(iconComponent.props('name')).toBe('exclamation');
  });

  test('should display a <ul> element when there are multiple messages', () => {
    const component = createComponent();
    expect(getParagraph(component).exists()).toBeFalsy();

    const list = getList(component);
    expect(list.element.childElementCount).toBe(2);

    expect(list.text()).toContain('mocked_message_1');
    expect(list.text()).toContain('mocked_message_2');
  });

  test('should display a <p> element when there is a single message', () => {
    const component = createComponent({ messages: ['mocked_message'] });
    expect(getList(component).exists()).toBeFalsy();

    expect(getParagraph(component).text()).toBe('mocked_message');
  });

  test('should display nothing when no messages are provided', () => {
    const component = createComponent({ messages: [] });
    expect(component.text().trim()).toBe('');
  });
});
