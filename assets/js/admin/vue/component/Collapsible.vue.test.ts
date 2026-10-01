import Collapsible from '@admin-fe/component/Collapsible.vue';
import { VueWrapper, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { h, nextTick } from 'vue';

describe('The "Collapsible" component', () => {
  let animationFrameCallbacks: FrameRequestCallback[];

  const createComponent = (isCollapsed = false, id?: string) => {
    const wrapper = mount(Collapsible, {
      props: {
        modelValue: isCollapsed,
        id,
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

  beforeEach(() => {
    vi.useFakeTimers();
    animationFrameCallbacks = [];
    vi.stubGlobal('requestAnimationFrame', (callback: FrameRequestCallback) => {
      animationFrameCallbacks.push(callback);
      return animationFrameCallbacks.length;
    });
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.useRealTimers();
  });

  const runNextAnimationFrame = async () => {
    const callbacks = animationFrameCallbacks.splice(0);
    callbacks.forEach((callback) => callback(0));
    await nextTick();
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

  const getVisibility = (component: VueWrapper) =>
    getCollapsingElement(component).element.style.visibility;

  const collapse = async (component: VueWrapper) => {
    await component.setProps({ modelValue: true });
    await component.vm.$nextTick();
    await runNextAnimationFrame();
    await runNextAnimationFrame();
    await getCollapsingElement(component).trigger('transitionend');
  };
  const expand = async (component: VueWrapper) => {
    await component.setProps({ modelValue: false });
    await component.vm.$nextTick();
    await runNextAnimationFrame();
    await runNextAnimationFrame();
    await getCollapsingElement(component).trigger('transitionend');
  };

  test('should set the given id on the collapsing element', () => {
    const component = createComponent(false, 'mocked-id');

    expect(getCollapsingElement(component).attributes('id')).toBe('mocked-id');
  });

  test('should not set an id attribute when none is given', () => {
    const component = createComponent();

    expect(getCollapsingElement(component).attributes('id')).toBeUndefined();
  });

  describe('when collapsing', () => {
    test('should collapse the content by setting height: 0 and overflow: hidden', async () => {
      const component = createComponent();
      expect(isCollapsed(component)).toBe(false);

      await collapse(component);
      expect(isCollapsed(component)).toBe(true);
    });

    test('should keep the content visible during the transition and hide it afterwards', async () => {
      const component = createComponent();

      await component.setProps({ modelValue: true });
      await component.vm.$nextTick();
      await runNextAnimationFrame();
      await runNextAnimationFrame();
      expect(getVisibility(component)).toBe('');

      await getCollapsingElement(component).trigger('transitionend');
      expect(getVisibility(component)).toBe('hidden');
    });

    test('should hide the content when initially collapsed', async () => {
      const component = createComponent(true);
      await component.vm.$nextTick();

      expect(getVisibility(component)).toBe('hidden');
    });

    test('should in the end emit an "collapsed" event', async () => {
      const component = createComponent();
      expect(component.emitted().collapsed).toBeUndefined();

      await collapse(component);
      expect(component.emitted().collapsed).toHaveLength(1);
    });

    test('should emit "collapsed" when transitionend does not fire', async () => {
      const component = createComponent();

      await component.setProps({ modelValue: true });
      await component.vm.$nextTick();
      await runNextAnimationFrame();
      await runNextAnimationFrame();

      vi.advanceTimersByTime(499);
      expect(component.emitted().collapsed).toBeUndefined();

      vi.advanceTimersByTime(1);
      expect(component.emitted().collapsed).toHaveLength(1);
    });

    test('should not emit twice when transitionend and fallback both fire', async () => {
      const component = createComponent();

      await component.setProps({ modelValue: true });
      await component.vm.$nextTick();
      await runNextAnimationFrame();
      await runNextAnimationFrame();
      await getCollapsingElement(component).trigger('transitionend');

      vi.advanceTimersByTime(500);
      expect(component.emitted().collapsed).toHaveLength(1);
    });

    test('should not complete a previous collapse after toggling to expand', async () => {
      const component = createComponent();

      await component.setProps({ modelValue: true });
      await component.vm.$nextTick();
      await runNextAnimationFrame();
      await runNextAnimationFrame();

      await component.setProps({ modelValue: false });
      await component.vm.$nextTick();
      await runNextAnimationFrame();
      await runNextAnimationFrame();
      vi.advanceTimersByTime(500);
      await nextTick();

      expect(component.emitted().collapsed).toBeUndefined();
      expect(isExpanded(component)).toBe(true);
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

    test('should make the content visible again as soon as expanding starts', async () => {
      const component = createComponent(true);
      await component.vm.$nextTick();
      expect(getVisibility(component)).toBe('hidden');

      await component.setProps({ modelValue: false });
      expect(getVisibility(component)).toBe('');
    });

    test('should reset the height and overflow properties when transitionend does not fire', async () => {
      const component = createComponent(true);
      await component.vm.$nextTick();

      await component.setProps({ modelValue: false });
      await component.vm.$nextTick();
      await runNextAnimationFrame();
      await runNextAnimationFrame();
      vi.advanceTimersByTime(500);
      await nextTick();

      expect(isExpanded(component)).toBe(true);
    });
  });
});
