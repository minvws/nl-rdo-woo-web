import { mount, VueWrapper } from '@vue/test-utils';
import { useFocusWithin } from '@vueuse/core';
import {
  afterEach,
  beforeEach,
  describe,
  expect,
  MockInstance,
  test,
  vi,
} from 'vitest';
import { nextTick, Ref, ref } from 'vue';
import PublicationSearchForm from './PublicationSearchForm.vue';

let onEscapeCallback: any;

vi.mock('@vueuse/core', () => ({
  useFocusWithin: vi.fn(),
}));

vi.mock('@utils', () => ({
  onKeyDown: vi.fn().mockImplementation((key, callback) => {
    if (key === 'Escape') {
      onEscapeCallback = callback;
    }
  }),
}));

describe('The "PublicationSearchForm" component', () => {
  const createComponent = () => mount(PublicationSearchForm);
  const getFormElement = (component: VueWrapper) => component.find('form');

  let focusedRef: Ref<boolean>;

  beforeEach(() => {
    vi.useFakeTimers();
    focusedRef = ref(false);
    (useFocusWithin as unknown as MockInstance).mockReturnValue({
      focused: focusedRef,
    });
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  const setFocus = async () => {
    focusedRef.value = true;
    await nextTick();
  };

  const unsetFocus = async () => {
    focusedRef.value = false;
    await nextTick();
    vi.advanceTimersByTime(101); // Wait for the timer to execute
  };

  test('it should render a form with the correct attributes', () => {
    const component = createComponent();
    const formElement = getFormElement(component);

    expect(formElement.attributes('method')).toBe('get');
    expect(formElement.attributes('class')).toBe('relative');
  });

  test('it should emit "focusOut" when the form loses focus', async () => {
    const component = createComponent();

    await setFocus();
    expect(component.emitted('focusOut')).toBeFalsy();

    await unsetFocus();
    expect(component.emitted('focusOut')).toBeTruthy();
  });

  test('it should emit "escape" when the user presses escape', async () => {
    const component = createComponent();

    await setFocus();
    expect(component.emitted('escape')).toBeFalsy();

    onEscapeCallback();
    await nextTick();

    expect(component.emitted('escape')).toBeTruthy();

    component.unmount();
  });
});
