import Collapsible from '@admin-fe/component/Collapsible.vue';
import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import { h } from 'vue';

describe('The "Collapsible" component', () => {
  const createComponent = (isCollapsed = false) => {
    const wrapper = mount(Collapsible, {
      props: {
        modelValue: isCollapsed,
        'onUpdate:modelValue': (newIsCollapsedValue: boolean) =>
          wrapper.setProps({ modelValue: newIsCollapsedValue }),
      },
      slots: {
        default: h(
          'div',
          { style: { height: '100px' } },
          'This is the mocked provided content',
        ),
      },
      shallow: true,
    });

    return wrapper;
  };

  const getCollapsingElement = (component: VueWrapper) => component.find('div');
  const isCollapsed = (component: VueWrapper) => {
    const { height, overflow } = getCollapsingElement(component).element.style;
    return height === '0px' && overflow === 'hidden';
  };

  const isExpanded = (component: VueWrapper) => {
    const { height, overflow } = getCollapsingElement(component).element.style;
    return Boolean(height === '' && overflow === '');
  };

  const collapse = async (component: VueWrapper) => {
    await component.setProps({ modelValue: true });
    await component.vm.$nextTick();
    getCollapsingElement(component).trigger('transitionend');
  };
  const expand = async (component: VueWrapper) => {
    component.setProps({ modelValue: false });
    await component.vm.$nextTick();
    getCollapsingElement(component).trigger('transitionend');
  };

  describe('when collapsing', () => {
    test('should collapse the content by setting height: 0 and overflow: hidden', async () => {
      const component = createComponent();
      expect(isCollapsed(component)).toBe(false);

      await collapse(component);
      expect(isCollapsed(component)).toBe(true);
    });

    test('should in the end emit an "collapsed" event', async () => {
      const component = createComponent();
      expect(component.emitted().collapsed).toBeUndefined();

      await collapse(component);
      expect(component.emitted().collapsed).toHaveLength(1);
    });
  });

  describe('when expanded', () => {
    test('should reset the height and overflow properties', async () => {
      const component = createComponent(true);
      await component.vm.$nextTick();

      expect(isExpanded(component)).toBe(false);

      await expand(component);
      expect(isExpanded(component)).toBe(true);
    });
  });
});
