import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { isElementVisible } from '../utils';
import { mainNav } from './main-nav';

vi.mock('../utils/browser', () => ({
  getWindow: () => ({
    matchMedia: () => ({ matches: true }), // disables animation
  }),
}));

describe('The functionality regarding the main navigation', () => {
  let cleanup: () => void;
  let initialize: () => void;

  beforeEach(() => {
    vi.useFakeTimers();
    document.body.innerHTML = `
      <button id="js-main-nav-toggle" aria-controls="main-nav">Toggle visibility of list</button>
      <ul id="main-nav" class="hidden"><li>Test</li></ul>
    `;

    ({ cleanup, initialize } = mainNav());
    initialize();
  });

  afterEach(() => {
    cleanup();
    vi.useRealTimers();
  });

  const getButton = () => document.getElementById('js-main-nav-toggle') as HTMLButtonElement;
  const toggleVisibility = () => getButton().click();
  const getNavigation = () => document.getElementById('main-nav') as HTMLUListElement;
  const isNavigationVisible = () => isElementVisible(getNavigation());

  test('should display the navigation when the toggle button is clicked', () => {
    expect(isNavigationVisible()).toBe(false);
    toggleVisibility();
    expect(isNavigationVisible()).toBe(true);
  });

  test('should hide the navigation when the toggle button is clicked while the navigation is currently visible', () => {
    toggleVisibility();
    expect(isNavigationVisible()).toBe(true);

    toggleVisibility();
    expect(isNavigationVisible()).toBe(false);
  });
});
