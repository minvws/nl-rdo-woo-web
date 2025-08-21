import { VueWrapper, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import Combobox from './Combobox.vue';
import { useInputStore } from '../../composables';
import { nextTick } from 'vue';

vi.mock('@admin-fe/composables', () => ({
  useInputStore: vi.fn().mockReturnValue({
    isMockedInputStore: true,
    errors: [],
    hasVisibleErrors: false,
    markAsTouched: vi.fn(),
    setValidators: vi.fn(),
  }),
  useInputAriaDescribedBy: vi.fn().mockReturnValue('mocked-aria-describedby'),
}));

describe('The "Combobox" component', () => {
  const scrollIntoViewSpy = vi.fn();

  interface Options {
    autoFocus: boolean;
    forbiddenValues: string[];
    value: string;
  }

  const createComponent = (options: Partial<Options> = {}) => {
    return mount(Combobox, {
      props: {
        autoFocus: options.autoFocus,
        forbiddenValues: options.forbiddenValues ?? [],
        label: 'Mocked label',
        maxLength: 10,
        minLength: 2,
        options: ['Mocked option 1', 'Mocked option 2', 'Mocked option 3'],
        value: options.value ?? '',
      },
      global: {
        renderStubDefaultSlot: true,
      },
      shallow: true,
    });
  };

  const getRemovableInputComponent = (component: VueWrapper) =>
    component.findComponent({
      name: 'RemovableInput',
    });

  const getInputElement = (component: VueWrapper) => component.find('input');
  const getInputElementValue = (component: VueWrapper) =>
    getInputElement(component).element.value;

  const getOptionsListElement = (component: VueWrapper) =>
    component.find('ul[role="listbox"]');

  const getSelectedOption = (component: VueWrapper) =>
    component.find('li[aria-selected="true"]');

  const getActiveOption = (component: VueWrapper) =>
    component.find('.bhr-combobox__option--active');

  const pressKey = (component: VueWrapper, key: string) =>
    getInputElement(component).trigger(key);

  const isOptionsListVisible = (component: VueWrapper) =>
    !getOptionsListElement(component).classes().includes('hidden');

  const getOptionsListItems = (component: VueWrapper) =>
    getOptionsListElement(component).findAll('li');

  const showList = (component: VueWrapper) =>
    pressKey(component, 'keyup.alt.down');

  beforeEach(() => {
    HTMLLIElement.prototype.scrollIntoView = scrollIntoViewSpy;
    vi.clearAllMocks();
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  test('should render a <RemovableInput /> component', () => {
    expect(getRemovableInputComponent(createComponent()).exists()).toBe(true);
  });

  test('should emit a "delete" event when the <RemovableInput /> component emits a "delete" event', async () => {
    const component = createComponent();

    expect(component.emitted('delete')).toBeFalsy();

    await getRemovableInputComponent(component).vm.$emit('delete');
    expect(component.emitted('delete')?.[0]).toMatchObject([
      expect.objectContaining({
        errors: [],
        hasVisibleErrors: false,
      }),
    ]);
  });

  test('should emit an "update" event when the input field is updated', async () => {
    const component = createComponent();

    expect(component.emitted('update')).toBeFalsy();

    await getInputElement(component).setValue('Mocked option 2');
    expect(component.emitted('update')?.[0][0]).toBe('Mocked option 2');
  });

  test('should emit a "mounted" event when the component is mounted', async () => {
    const component = createComponent();

    expect(component.emitted('mounted')?.[0][0]).toMatchObject({
      isMockedInputStore: true,
    });
  });

  describe('the list with options', () => {
    test('should not be visible by default', async () => {
      const component = createComponent();
      await nextTick();

      expect(isOptionsListVisible(component)).toBe(false);
    });

    test('should be visible if the prop "autoFocus" is set to true', async () => {
      const component = createComponent({ autoFocus: true });
      await nextTick();

      expect(isOptionsListVisible(component)).toBe(true);
    });

    test('should contain the provided options', () => {
      const component = createComponent();
      const options = getOptionsListItems(component);

      expect(options.length).toBe(3);
      expect(options.at(0)?.text()).toBe('Mocked option 1');
      expect(options.at(1)?.text()).toBe('Mocked option 2');
      expect(options.at(2)?.text()).toBe('Mocked option 3');
    });

    test('should only contain those options starting with the provided value', () => {
      const component = createComponent({ value: 'Mocked option 2' });
      const options = getOptionsListItems(component);

      expect(options.length).toBe(1);
      expect(options.at(0)?.text()).toBe('Mocked option 2');
    });

    test('should only contain those options which are not found in the forbidden values', () => {
      const component = createComponent({
        forbiddenValues: ['Mocked option 2'],
      });
      const options = getOptionsListItems(component);

      expect(options.length).toBe(2);
      expect(options.at(0)?.text()).toBe('Mocked option 1');
      expect(options.at(1)?.text()).toBe('Mocked option 3');
    });

    test('should have an option marked as selected when the option equals the value', () => {
      const component = createComponent({ value: 'Mocked option 2' });

      expect(getSelectedOption(component).text()).toBe('Mocked option 2');
    });

    test('should have the date-e2e-name="combobox-option" attribute on each option', () => {
      const component = createComponent();
      const options = getOptionsListItems(component);

      for (let i = 0; i < options.length; i++) {
        expect(options.at(i)?.attributes('data-e2e-name')).toBe(
          'combobox-option',
        );
      }
    });

    describe('navigating through the options', () => {
      test('should make the options list visible when the user presses ALT + DOWN', async () => {
        const component = createComponent();
        expect(isOptionsListVisible(component)).toBe(false);

        await pressKey(component, 'keyup.alt.down');
        expect(isOptionsListVisible(component)).toBe(true);
      });

      describe('when the user presses DOWN', () => {
        test('should make the options list visible', async () => {
          const component = createComponent();
          expect(isOptionsListVisible(component)).toBe(false);

          await pressKey(component, 'keyup.down');
          expect(isOptionsListVisible(component)).toBe(true);
        });

        test('should make the next option selected', async () => {
          const component = createComponent();

          await pressKey(component, 'keyup.down.exact');
          expect(getActiveOption(component).text()).toBe('Mocked option 1');

          await pressKey(component, 'keyup.down.exact');
          expect(getActiveOption(component).text()).toBe('Mocked option 2');

          await pressKey(component, 'keyup.down.exact');
          expect(getActiveOption(component).text()).toBe('Mocked option 3');

          await pressKey(component, 'keyup.down.exact');
          expect(getActiveOption(component).text()).toBe('Mocked option 1');
        });
      });

      describe('when the user presses UP', () => {
        test('should make the options list visible', async () => {
          const component = createComponent();
          expect(isOptionsListVisible(component)).toBe(false);

          await pressKey(component, 'keyup.down');
          expect(isOptionsListVisible(component)).toBe(true);
        });

        test('should make the previous option selected', async () => {
          const component = createComponent();

          await pressKey(component, 'keyup.up.exact');
          expect(getActiveOption(component).text()).toBe('Mocked option 3');

          await pressKey(component, 'keyup.up.exact');
          expect(getActiveOption(component).text()).toBe('Mocked option 2');

          await pressKey(component, 'keyup.up.exact');
          expect(getActiveOption(component).text()).toBe('Mocked option 1');

          await pressKey(component, 'keyup.up.exact');
          expect(getActiveOption(component).text()).toBe('Mocked option 3');
        });
      });

      describe('when the user presses ENTER', () => {
        test('should hide the options list if there is no active option currently', async () => {
          const component = createComponent();
          expect(isOptionsListVisible(component)).toBe(false);

          await showList(component);
          expect(isOptionsListVisible(component)).toBe(true);

          await pressKey(component, 'keypress.enter');
          expect(isOptionsListVisible(component)).toBe(false);
        });

        test('should update the value of the input field when there is an active option', async () => {
          const component = createComponent();

          await showList(component);
          await pressKey(component, 'keyup.down.exact'); // Mocked option 1 is active
          await pressKey(component, 'keyup.down.exact'); // Mocked option 2 is active
          await pressKey(component, 'keypress.enter');

          expect(getInputElementValue(component)).toBe('Mocked option 2');
        });
      });

      describe('when the user presses ESC', () => {
        test('should hide the options list if the list is visible', async () => {
          const component = createComponent();
          expect(isOptionsListVisible(component)).toBe(false);

          await showList(component);
          expect(isOptionsListVisible(component)).toBe(true);

          await pressKey(component, 'keyup.esc');
          expect(isOptionsListVisible(component)).toBe(false);
        });

        test('should clear the value of the input field if the list is visible', async () => {
          const component = createComponent({ value: 'Mocked option 2' });

          await pressKey(component, 'keyup.esc');
          expect(getInputElementValue(component)).toBe('');
        });
      });
    });
  });

  test('should update the value when the user selects an option', async () => {
    const component = createComponent();

    await showList(component);
    await getOptionsListItems(component).at(1)?.trigger('click');
    expect(getInputElementValue(component)).toBe('Mocked option 2');

    await getOptionsListItems(component).at(2)?.trigger('click');
    expect(getInputElementValue(component)).toBe('Mocked option 3');
  });

  test('should display a button which toggles the visibility of the options list', async () => {
    const component = createComponent();

    await component.find('button').trigger('click');
    expect(isOptionsListVisible(component)).toBe(true);

    await component.find('button').trigger('click');
    expect(isOptionsListVisible(component)).toBe(false);
  });

  test('should mark the input field as touched and hide the options list when the user blurs the input field', async () => {
    const component = createComponent();

    await showList(component);

    expect((useInputStore as any)().markAsTouched).not.toHaveBeenCalled();
    expect(isOptionsListVisible(component)).toBe(true);

    await getInputElement(component).trigger('blur');
    vi.advanceTimersByTime(200);
    await nextTick();

    expect((useInputStore as any)().markAsTouched).toHaveBeenCalled();
    expect(isOptionsListVisible(component)).toBe(false);
  });

  test('it should set the validators when the forbidden values change', async () => {
    const component = createComponent({
      forbiddenValues: ['Mocked option 2'],
    });

    await component.setProps({ forbiddenValues: ['Mocked option 2'] });
    expect((useInputStore as any)().setValidators).not.toHaveBeenCalled();

    await component.setProps({ forbiddenValues: ['Mocked option 3'] });
    expect((useInputStore as any)().setValidators).toHaveBeenCalledWith(
      expect.arrayContaining([expect.any(Function)]),
    );
  });

  test('should update the value of the input field when the "value" prop changes', async () => {
    const component = createComponent({ value: 'Mocked option 2' });

    await component.setProps({ value: 'Mocked option 3' });
    expect(getInputElementValue(component)).toBe('Mocked option 3');
  });
});
