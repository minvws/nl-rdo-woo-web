import { beforeEach, describe, expect, test, vi } from 'vitest';
import { isFocusWithinElement } from './focus';

vi.mock('./browser');

describe('The "isFocusWithinElement" function', () => {
  const getContainer = () =>
    document.getElementById('container') as HTMLElement;
  const getButton = () => document.querySelector('button') as HTMLElement;
  const getAnchor = () => document.querySelector('a') as HTMLElement;

  beforeEach(() => {
    document.body.innerHTML = `
      <div id="container">
        <button>
          This is a button
        </button>
      </div>
      <a href="https://open.minsvws.nl">Anchor</a>
    `;
  });

  test('should return false when no element has focus', () => {
    expect(isFocusWithinElement(getButton())).toBe(false);
  });

  test('should return false when an element outside the provided element has focus', () => {
    getAnchor().focus();
    expect(isFocusWithinElement(getButton())).toBe(false);
  });

  test('should return true when an element inside the provided element has focus', () => {
    getButton().focus();
    expect(isFocusWithinElement(getContainer())).toBe(true);
  });

  test('should return true when the provided element itself has focus', () => {
    const buttonElement = getButton();
    buttonElement.focus();
    expect(isFocusWithinElement(buttonElement)).toBe(true);
  });
});
