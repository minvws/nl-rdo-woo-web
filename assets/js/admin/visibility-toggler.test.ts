import { afterEach, beforeEach, describe, expect, test } from 'vitest';
import { isElementHidden } from '@utils';
import { visibilityToggler } from './visibility-toggler';

describe('the "visibilityToggler" function', () => {
  let cleanup: () => void;
  let initialize: () => void;

  const getToggleButton = () =>
    document.querySelector('.js-visibility-toggler') as HTMLElement;

  beforeEach(() => {
    document.body.innerHTML = `
      <button class="js-visibility-toggler" aria-controls="id-of-element-to-toggle" aria-expanded="true">Toggle visibility</button>
      <div id="id-of-element-to-toggle">This element should be toggled</div>
    `;

    ({ cleanup, initialize } = visibilityToggler());
    initialize();
  });

  afterEach(() => {
    cleanup();
  });

  test('should toggle the visibility of an element when clicking a button with the "js-visibility-toggler" class name', () => {
    const toggleButton = getToggleButton();
    const elementToToggle = document.getElementById(
      'id-of-element-to-toggle',
    ) as HTMLElement;

    expect(isElementHidden(elementToToggle)).toBe(false);

    toggleButton?.click();
    expect(isElementHidden(elementToToggle)).toBe(true);

    toggleButton?.click();
    expect(isElementHidden(elementToToggle)).toBe(false);
  });

  test('should adjust the "aria-expanded" value based on the visibility of the target element', () => {
    const toggleButton = getToggleButton();
    const getAriaExpandedValue = () =>
      toggleButton?.getAttribute('aria-expanded');

    expect(getAriaExpandedValue()).toBe('true');

    toggleButton?.click();
    expect(getAriaExpandedValue()).toBe('false');

    toggleButton?.click();
    expect(getAriaExpandedValue()).toBe('true');
  });
});
