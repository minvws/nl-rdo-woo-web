import { describe, expect, test } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Pending from './Pending.vue';

describe('Pending.vue', () => {
  const createComponent = () => {
    return mount(Pending, {
      props: {
        isPending: false,
      },
      global: {
        renderStubDefaultSlot: true,
      },
      slots: {
        default: 'This is the mocked provided content',
      },
      shallow: true,
    });
  };

  const getContentElement = (component: VueWrapper) =>
    component.find('.transition-all');

  const getSpinnerComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'Spinner' });

  test('should render the provided content', () => {
    expect(getContentElement(createComponent()).text()).toBe(
      'This is the mocked provided content',
    );
  });

  test('should blur the provided content when the isPending prop is true', async () => {
    const component = createComponent();

    expect(getContentElement(component).attributes('class')).not.toContain(
      'blur-xs',
    );

    await component.setProps({ isPending: true });
    expect(getContentElement(component).attributes('class')).toContain(
      'blur-xs',
    );
  });

  test('should render the spinner when the isPending prop is true', async () => {
    const component = createComponent();
    expect(getSpinnerComponent(component).exists()).toBe(false);

    await component.setProps({ isPending: true });
    expect(getSpinnerComponent(component).exists()).toBe(true);
  });
});
