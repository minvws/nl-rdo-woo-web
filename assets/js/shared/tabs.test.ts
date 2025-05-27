import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { tabs } from './tabs';
import { getLocation } from '@js/utils';

vi.mock('@js/utils');

describe('The "tabs" function', () => {
  let cleanup: () => void;
  let initialize: () => void;

  beforeEach(() => {
    const renderTabsList = (name: string) => `
      <div class="js-tabs">
        <ul role="tablist">
          <li>
            <button
              aria-controls="tabs-${name}-content-1"
              aria-selected="true"
              data-tab-target="#tabs-${name}-button-1"
              id="tabs-${name}-button-1"
              role="tab"
            >
              Tabs ${name} button 1
            </button>
          </li>
          <li>
            <button
              aria-controls="tabs-${name}-content-2"
              data-tab-target="#tabs-${name}-button-2"
              id="tabs-${name}-button-2"
              role="tab"
              tabindex="-1"
            >
              Tabs ${name} button 2
            </button>
          </li>
          <li>
            <button
              aria-controls="tabs-${name}-content-3"
              data-tab-target="#tabs-${name}-button-3"
              id="tabs-${name}-button-3"
              role="tab"
              tabindex="-1"
            >
              Tabs ${name} button 3
            </button>
          </li>
        </ul>

        <div id="tabs-${name}-content-1" role="tabpanel" tabindex="0">Tabs ${name} content 1</div>
        <div id="tabs-${name}-content-2" role="tabpanel" tabindex="0" hidden>Tabs ${name} content 2</div>
        <div id="tabs-${name}-content-3" role="tabpanel" tabindex="0" hidden>Tabs ${name} content 3</div>
      </div>
    `;
    document.body.innerHTML = `${renderTabsList('one')} ${renderTabsList('two')} <div class="js-tabs">empty</div>`;

    ({ cleanup, initialize } = tabs());

    getLocation().hash = '#tabs-two-button-2';

    initialize();
  });

  const navigate = (direction: 'left' | 'right') => {
    const key = direction === 'left' ? 'ArrowLeft' : 'ArrowRight';
    const event = new KeyboardEvent('keydown', { key });
    document.querySelector('[role="tablist"]')?.dispatchEvent(event);
  };

  const getTabsWrappers = () =>
    document.querySelectorAll<HTMLDivElement>('.js-tabs');

  const getTabsWrapper = (tabsWrapperIndex: number) =>
    getTabsWrappers()[tabsWrapperIndex];

  const getButtons = (tabsWrapperIndex: number) => [
    ...getTabsWrapper(tabsWrapperIndex).querySelectorAll<HTMLButtonElement>(
      '[role="tab"]',
    ),
  ];

  const getSelectedButton = (
    tabsWrapperIndex: number,
  ): HTMLButtonElement | undefined =>
    getButtons(tabsWrapperIndex).find(
      (button) => button.getAttribute('aria-selected') === 'true',
    );

  const getElementContent = (element?: Element | null) =>
    element?.textContent?.trim();

  const getSelectedPanel = (tabsWrapperIndex: number): HTMLDivElement | null =>
    getTabsWrapper(tabsWrapperIndex).querySelector<HTMLDivElement>(
      '[role="tabpanel"]:not([hidden])',
    );

  afterEach(() => {
    cleanup();
  });

  test('should activate the tab with the same id as the hash', () => {
    expect(getElementContent(getSelectedButton(1))).toBe('Tabs two button 2');
    expect(getElementContent(getSelectedPanel(1))).toBe('Tabs two content 2');
  });

  test('should activate the correspondig tab when clicking a tab button', () => {
    getButtons(1)[0]?.click();

    expect(getElementContent(getSelectedButton(1))).toBe('Tabs two button 1');
    expect(getElementContent(getSelectedPanel(1))).toBe('Tabs two content 1');
  });

  describe('navigation', () => {
    test('should focus on the next tab button and activate it when the right arrow key is pressed', () => {
      getButtons(0)[1].focus();

      navigate('right');
      expect(document.activeElement).toBe(getButtons(0)[2]);
      expect(getElementContent(getSelectedButton(0))).toBe('Tabs one button 3');
      expect(getElementContent(getSelectedPanel(0))).toBe('Tabs one content 3');

      navigate('right');
      expect(document.activeElement).toBe(getButtons(0)[0]);
      expect(getElementContent(getSelectedButton(0))).toBe('Tabs one button 1');
      expect(getElementContent(getSelectedPanel(0))).toBe('Tabs one content 1');
    });

    test('should focus on the previous tab button and activate it when the left arrow key is pressed', () => {
      getButtons(0)[1].focus();

      navigate('left');
      expect(document.activeElement).toBe(getButtons(0)[0]);
      expect(getElementContent(getSelectedButton(0))).toBe('Tabs one button 1');
      expect(getElementContent(getSelectedPanel(0))).toBe('Tabs one content 1');

      navigate('left');
      expect(document.activeElement).toBe(getButtons(0)[2]);
      expect(getElementContent(getSelectedButton(0))).toBe('Tabs one button 3');
      expect(getElementContent(getSelectedPanel(0))).toBe('Tabs one content 3');
    });
  });

  describe('when activating a tab', () => {
    test('should set "tabindex" to "-1" for all other buttons', () => {
      getButtons(0)[1].click();

      const buttons = getButtons(0);

      expect(buttons[0].getAttribute('tabindex')).toBe('-1');
      expect(buttons[1].getAttribute('tabindex')).toBe(null);
      expect(buttons[2].getAttribute('tabindex')).toBe('-1');
    });
  });
});
