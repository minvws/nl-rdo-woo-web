import { afterEach, beforeEach, describe, expect, test } from 'vitest';
import { clickOnSelector } from './click-on-selector';

describe('the "clickOnSelector" function', () => {
  let cleanup: () => void;
  let initialize: () => void;

  let abortController: AbortController;
  let isTargetButtonClicked = false;

  beforeEach(() => {
    document.body.innerHTML = `
      <dialog>
        <button class="js-click-on-selector" data-selector="submit-form-outside-dialog">Open dialog</button>
      </dialog>
      <button id="submit-form-outside-dialog">Submit form</button>
    `;

    ({ cleanup, initialize } = clickOnSelector());
    initialize();

    isTargetButtonClicked = false;
    abortController = new AbortController();

    getTargetButton().addEventListener('click', () => {
      isTargetButtonClicked = true;
    }, { signal: abortController.signal });
  });

  afterEach(() => {
    cleanup();
    abortController.abort();
    isTargetButtonClicked = false;
  });

  const getSourceButton = () => document.querySelector('.js-click-on-selector') as HTMLButtonElement;
  const getTargetButton = () => document.getElementById('submit-form-outside-dialog') as HTMLButtonElement;

  describe('clicking an element having the "js-click-on-selector" class name', () => {
    test('should trigger a click on the element it refers to', () => {
      expect(isTargetButtonClicked).toBe(false);
      getSourceButton().click();
      expect(isTargetButtonClicked).toBe(true);
    });
  });
});
