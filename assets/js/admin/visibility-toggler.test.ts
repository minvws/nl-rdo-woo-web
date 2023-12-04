import { beforeEach, describe, expect, test } from '@jest/globals';
import { isElementHidden } from '@utils';
import { visibilityToggler } from './visibility-toggler';

describe('the "visibilityToggler" function', () => {
  let cleanup: () => void;
  let initialize: () => void;

  beforeEach(() => {
    document.body.innerHTML = `
      <button class="js-visibility-toggler" data-selector="id-of-element-to-toggle">Toggle visibility</button>
      <div id="id-of-element-to-toggle">This element should be toggled</div>
    `;

    ({ cleanup, initialize } = visibilityToggler());
    initialize();
  });

  afterEach(() => {
    cleanup();
  });

  test('should toggle the visibility of an element when clicking a button with the "js-visibility-toggler" class name', () => {
    const toggleButton = document.querySelector('.js-visibility-toggler') as HTMLElement;
    const elementToToggle = document.getElementById('id-of-element-to-toggle') as HTMLElement;

    expect(isElementHidden(elementToToggle)).toBe(false);

    toggleButton?.click();
    expect(isElementHidden(elementToToggle)).toBe(true);

    toggleButton?.click();
    expect(isElementHidden(elementToToggle)).toBe(false);
  });
});
