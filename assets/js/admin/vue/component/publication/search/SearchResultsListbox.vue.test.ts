import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import { nextTick } from 'vue';
import SearchResultsListbox from './SearchResultsListbox.vue';

describe('The "SearchResultsListbox" component', () => {
  const createResult = (id: string) => ({ id, title: `mocked-${id}` });
  const mockedResults = [createResult('abc'), createResult('def')];

  const createComponent = () =>
    mount(SearchResultsListbox, {
      props: {
        isVisible: false,
        results: mockedResults,
      },
      shallow: true,
    });

  const getResultItems = (component: VueWrapper) => component.findAll('li');
  const getResultItem = (component: VueWrapper, index: number) =>
    getResultItems(component).at(index);
  const isResultSelected = (component: VueWrapper, index: number) =>
    getResultItem(component, index)?.attributes('aria-selected') === 'true';

  const pressKey = async (key: string) => {
    window.dispatchEvent(new KeyboardEvent('keydown', { key }));
    await nextTick();
  };

  const setVisibility = async (component: VueWrapper, isVisible: boolean) => {
    await component.setProps({ isVisible });
    await nextTick();
  };

  test('should render the provided results as list items', () => {
    const component = createComponent();
    const items = getResultItems(component);
    const firstItem = items.at(0);

    expect(items).toHaveLength(2);
    expect(firstItem?.text()).toBe('mocked-abc');
    expect(firstItem?.attributes('aria-selected')).toBe('false');
    expect(firstItem?.attributes('role')).toBe('option');
  });

  test('should emit a "select" event when a result is clicked', async () => {
    const component = createComponent();

    await getResultItem(component, 0)?.trigger('click');
    expect(component.emitted('select')?.[0]).toEqual([mockedResults[0]]);
  });

  test('should emit a "select" event when a result is hovered', async () => {
    const component = createComponent();

    await getResultItem(component, 0)?.trigger('pointerover');
    expect(isResultSelected(component, 0)).toBe(true);

    await getResultItem(component, 0)?.trigger('pointerout');
    expect(isResultSelected(component, 0)).toBe(false);
  });

  test('should mark the next result as selected when pressing the arrow down', async () => {
    const component = createComponent();
    await setVisibility(component, true);

    // None should be selected
    expect(isResultSelected(component, 0)).toBe(false);
    expect(isResultSelected(component, 1)).toBe(false);

    await pressKey('ArrowDown');
    // First should be selected
    expect(isResultSelected(component, 0)).toBe(true);
    expect(isResultSelected(component, 1)).toBe(false);

    await pressKey('ArrowDown');
    // Second should be selected
    expect(isResultSelected(component, 0)).toBe(false);
    expect(isResultSelected(component, 1)).toBe(true);

    await pressKey('ArrowDown');
    // First should be selected again
    expect(isResultSelected(component, 0)).toBe(true);
    expect(isResultSelected(component, 1)).toBe(false);

    await setVisibility(component, false);
  });

  test('should mark the previous result as selected when pressing the up down', async () => {
    const component = createComponent();
    await setVisibility(component, true);

    // None should be selected
    expect(isResultSelected(component, 0)).toBe(false);
    expect(isResultSelected(component, 1)).toBe(false);

    await pressKey('ArrowUp');
    // Last should be selected
    expect(isResultSelected(component, 0)).toBe(false);
    expect(isResultSelected(component, 1)).toBe(true);

    await pressKey('ArrowUp');
    // First should be selected
    expect(isResultSelected(component, 0)).toBe(true);
    expect(isResultSelected(component, 1)).toBe(false);

    await pressKey('ArrowUp');
    // Last should be selected again
    expect(isResultSelected(component, 0)).toBe(false);
    expect(isResultSelected(component, 1)).toBe(true);
  });

  describe('when the user presses the enter key', () => {
    test('should not emit a "select" event if no result is selected', async () => {
      const component = createComponent();
      await setVisibility(component, true);

      await pressKey('Enter');
      expect(component.emitted('select')).toBeFalsy();

      component.unmount();
    });

    test('should emit a "select" event with the selected result', async () => {
      const component = createComponent();
      await setVisibility(component, true);

      await pressKey('ArrowDown');
      await pressKey('Enter');
      expect(component.emitted('select')?.[0]).toEqual([mockedResults[0]]);
    });
  });
});
