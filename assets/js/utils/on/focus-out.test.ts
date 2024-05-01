import { beforeEach, describe, expect, test, vi } from 'vitest';
import { onFocusOut } from './focus-out';

describe('the "onFocusOut" function', () => {
  const getButtonElement = () => document.querySelector('button') as HTMLButtonElement;
  const getInsideButtonElement = () => document.querySelector('#inside-the-button') as HTMLAnchorElement;
  const getOutsideButtonElement = () => document.querySelector('#outside-the-button') as HTMLAnchorElement;

  beforeEach(() => {
    document.body.innerHTML = `
      <button>
        <a href="https://open.minsvws.nl" id="inside-the-button">Inside the button</a>
      </button>
      <a href="https://open.minsvws.nl" id="outside-the-button">Outside the button</a>
    `;
  });

  test('should invoke the prodived function when focus is no longer within the provided element', () => {
    const buttonElement = getButtonElement();
    const callbackFunction = vi.fn();

    onFocusOut(buttonElement, callbackFunction);

    getInsideButtonElement().focus();
    expect(callbackFunction).not.toHaveBeenCalled();

    getOutsideButtonElement().focus();
    expect(callbackFunction).toHaveBeenCalledTimes(1);
  });
});
