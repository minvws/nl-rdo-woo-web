import { afterEach, beforeEach, describe, expect, test } from 'vitest';
import { detailsComponents } from './details';

describe('The "detailsComponents" function', () => {
  let cleanup: () => void;
  let initialize: () => void;

  beforeEach(() => {
    document.body.innerHTML = `
      <button>Outside</button>
      <details class="js-details">
        <summary>Toggle details</summary>
        <p>Details</p>
      </details>
    `;
    ({ cleanup, initialize } = detailsComponents());
    initialize();
  });

  afterEach(() => {
    toggleDetailsElement(false);
    cleanup();
  });

  const getButtonElement = () => document.querySelector('button') as HTMLButtonElement;
  const toggleDetailsElement = (isOpen = true) => {
    const detailsElement = getDetailsElement();
    if (isOpen) {
      detailsElement.setAttribute('open', '');
    } else {
      detailsElement.removeAttribute('open');
    }
    detailsElement.dispatchEvent(new Event('toggle'));
  };
  const getDetailsElement = () => document.querySelector('.js-details') as HTMLDetailsElement;
  const isDetailsElementOpen = () => getDetailsElement().getAttribute('open') === '';

  describe('when a details element is opened', () => {
    beforeEach(() => {
      toggleDetailsElement();
    });

    test('should close when clicking outside of the details element', () => {
      expect(isDetailsElementOpen()).toBe(true);

      getButtonElement().click();
      expect(isDetailsElementOpen()).toBe(false);
    });

    test('should close when an element outside of the details element receives focus', () => {
      expect(isDetailsElementOpen()).toBe(true);

      getButtonElement().focus();
      expect(isDetailsElementOpen()).toBe(false);
    });

    test('should close when the "Escape" key is pressed', () => {
      expect(isDetailsElementOpen()).toBe(true);

      window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
      expect(isDetailsElementOpen()).toBe(false);
    });

    test('should do nothing when the functionality is cleaned up', () => {
      document.querySelector('summary')?.click();

      expect(isDetailsElementOpen()).toBe(false);
    });
  });
});
