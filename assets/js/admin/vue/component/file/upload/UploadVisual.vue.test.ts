import { mount, VueWrapper } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import UploadVisual from './UploadVisual.vue';
import { nextTick } from 'vue';

describe('The "<UploadVisual />" component', () => {
  const createComponent = () => mount(UploadVisual, { shallow: true });

  const coverWholePage = async (
    component: VueWrapper<InstanceType<typeof UploadVisual>>,
    shouldCoverWholePage: boolean,
  ) => {
    component.vm.coverWholePage(shouldCoverWholePage);
    await nextTick();
  };

  const slideInUp = async (
    component: VueWrapper<InstanceType<typeof UploadVisual>>,
  ) => {
    await component.vm.slideInUp();
  };

  const slideOutDown = async (
    component: VueWrapper<InstanceType<typeof UploadVisual>>,
  ) => {
    component.vm.slideOutDown();
    vi.advanceTimersByTime(200);
  };

  const slideOutUp = async (
    component: VueWrapper<InstanceType<typeof UploadVisual>>,
  ) => {
    component.vm.slideOutUp();
    vi.advanceTimersByTime(200);
  };

  const getUploadVisualElement = (component: VueWrapper) =>
    component.find('.bhr-upload-visual');

  const getUploadIconComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'Icon' });

  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  test('should render nothing by default', () => {
    expect(createComponent().text()).toBe('');
  });

  test('should cover the whole page when the exposed "coverWholePage" method is called with true', async () => {
    const component = createComponent();

    await slideInUp(component);

    const uploadVisualElement = getUploadVisualElement(component);
    expect(uploadVisualElement.classes()).toContain('absolute');
    expect(uploadVisualElement.classes()).not.toContain('fixed');

    await coverWholePage(component, true);
    expect(uploadVisualElement.classes()).toContain('fixed');
    expect(uploadVisualElement.classes()).not.toContain('absolute');
  });

  test('should display an upload icon when the exposed "slideInUp" method is called', async () => {
    const component = createComponent();

    expect(getUploadIconComponent(component).exists()).toBe(false);

    await slideInUp(component);

    expect(getUploadIconComponent(component).exists()).toBe(true);
  });

  test('should render the background blurred when the exposed "slideInUp" method is called', async () => {
    const component = createComponent();

    await slideInUp(component);

    expect(getUploadVisualElement(component).classes()).toContain(
      'backdrop-blur-xs',
    );
  });

  test('should hide its content when the exposed method "slideOutDown" or "slideOutUp" method is called', async () => {
    const component = createComponent();

    expect(component.text()).toBe('');

    await slideInUp(component);
    expect(component.text()).not.toBe('');

    await slideOutDown(component);
    expect(component.text()).toBe('');

    await slideInUp(component);
    expect(component.text()).not.toBe('');

    await slideOutUp(component);
    expect(component.text()).toBe('');
  });
});
