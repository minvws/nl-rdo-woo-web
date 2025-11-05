import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { isElementVisible } from '../utils';
import { collapsibles } from './collapsibles';

vi.mock('../utils/browser', () => ({
  getWindow: () => ({
    matchMedia: () => ({ matches: true }), // disables animation
  }),
}));

describe('The functionality regarding collapsibles', () => {
  let cleanup: () => void;
  let initialize: () => void;

  beforeEach(() => {
    vi.useFakeTimers();
    document.body.innerHTML = `
      <button class="js-collapsible-toggle" id="js-collapsible-toggle" aria-controls="collapsible">
        <span class="js-is-expanded hidden">Show less</span>
        <span class="js-is-collapsed">Show more</span>
      </button>
      <button class="js-collapsible-toggle" aria-controls="non-existing-collapsible">
        Toggle button without collapsing element
      </button>
      <div id="collapsible" class="hidden"><p>Test</p></div>
    `;

    ({ cleanup, initialize } = collapsibles());
    initialize();
  });

  afterEach(() => {
    cleanup();
    vi.useRealTimers();
  });

  const getToggleButton = () =>
    document.getElementById('js-collapsible-toggle') as HTMLButtonElement;
  const getCollapsibleElement = () =>
    document.getElementById('collapsible') as HTMLDivElement;
  const getExpandedToggleContent = () =>
    getToggleButton().querySelector('.js-is-expanded') as HTMLSpanElement;
  const getCollapsedToggleContent = () =>
    getToggleButton().querySelector('.js-is-collapsed') as HTMLSpanElement;

  const clickButton = () => {
    getToggleButton().click();
  };

  describe('when expanding the collapsible', () => {
    test('should display the content within the collapsable element', () => {
      expect(isElementVisible(getCollapsibleElement())).toBe(false);

      clickButton();
      expect(isElementVisible(getCollapsibleElement())).toBe(true);
    });

    test('should display the expanded toggle content', () => {
      expect(isElementVisible(getCollapsedToggleContent())).toBe(true);
      expect(isElementVisible(getExpandedToggleContent())).toBe(false);

      clickButton();
      expect(isElementVisible(getCollapsedToggleContent())).toBe(false);
      expect(isElementVisible(getExpandedToggleContent())).toBe(true);
    });
  });

  describe('when collapsing the collapsible', () => {
    test('should hide the content within the collapsable element', () => {
      clickButton(); // expand
      expect(isElementVisible(getCollapsibleElement())).toBe(true);

      clickButton(); // collapse
      expect(isElementVisible(getCollapsibleElement())).toBe(false);
    });

    test('should display the expanded toggle content', () => {
      clickButton(); // expand
      expect(isElementVisible(getCollapsedToggleContent())).toBe(false);
      expect(isElementVisible(getExpandedToggleContent())).toBe(true);

      clickButton(); // collapse
      expect(isElementVisible(getCollapsedToggleContent())).toBe(true);
      expect(isElementVisible(getExpandedToggleContent())).toBe(false);
    });
  });
});
