import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { tabs } from './tabs';
import { getLocation } from '@js/utils';

vi.mock('@js/utils');

describe('The "tabs" function', () => {
  let cleanup: () => void;
  let initialize: () => void;

  beforeEach(() => {
    document.body.innerHTML = `
      <div>
        <ul role="tablist">
          <li>
            <button
              aria-controls="tab1"
              data-tab-target="#tabcontrol-1"
              role="tab"
              aria-selected="true"
            >
              Tab button 1
            </button>
          </li>
          <li>
            <button
              aria-controls="tab2"
              data-tab-target="#tabcontrol-2"
              role="tab"
            >
              Tab button 2
            </button>
          </li>
          <li>
            <button
              aria-controls="tab3"
              data-tab-target="#tabcontrol-3"
              role="tab"
            >
              Tab button 3
            </button>
          </li>
        </ul>

        <div id="tab1" role="tabpanel">Tab content 1</div>
        <div id="tab2" role="tabpanel" hidden>Tab content 2</div>
        <div id="tab3" role="tabpanel" hidden>Tab content 3</div>
      </div>
    `;

    ({ cleanup, initialize } = tabs());

    getLocation().hash = '#tabcontrol-2';

    initialize();
  });

  const navigate = (direction: 'left' | 'right') => {
    const key = direction === 'left' ? 'ArrowLeft' : 'ArrowRight';
    const event = new KeyboardEvent('keydown', { key });
    document.querySelector('[role="tablist"]')?.dispatchEvent(event);
  };

  const getButtons = () => [
    ...document.querySelectorAll<HTMLButtonElement>('[role="tab"]'),
  ];

  const getSelectedButton = (): HTMLButtonElement | undefined =>
    getButtons().find(
      (button) => button.getAttribute('aria-selected') === 'true',
    );

  const getElementContent = (element?: Element | null) =>
    element?.textContent?.trim();

  const getSelectedPanel = () =>
    document.querySelector('[role="tabpanel"]:not([hidden])');

  afterEach(() => {
    cleanup();
  });

  test('should activate the tab with the same id as the hash', () => {
    expect(getElementContent(getSelectedButton())).toBe('Tab button 2');
    expect(getElementContent(getSelectedPanel())).toBe('Tab content 2');
  });

  test('should activate the correspondig tab when clicking a tab button', () => {
    getButtons()[0]?.click();

    expect(getElementContent(getSelectedButton())).toBe('Tab button 1');
    expect(getElementContent(getSelectedPanel())).toBe('Tab content 1');
  });

  describe('navigation', () => {
    test('should focus on the next tab button when the right arrow key is pressed', () => {
      getButtons()[1].focus();

      navigate('right');
      expect(document.activeElement).toBe(getButtons()[2]);

      navigate('right');
      expect(document.activeElement).toBe(getButtons()[0]);
    });

    test('should focus on the previous tab button when the ;eft arrow key is pressed', () => {
      getButtons()[1].focus();

      navigate('left');
      expect(document.activeElement).toBe(getButtons()[0]);

      navigate('left');
      expect(document.activeElement).toBe(getButtons()[2]);
    });
  });
});
