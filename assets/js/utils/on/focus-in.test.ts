import { beforeEach, describe, expect, test, vi } from 'vitest';
import { onFocusIn } from './focus-in';

describe('the "onFocusIn" function', () => {
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

  test('should invoke the prodived function when (a child element of) the provided element receives focus', () => {
    const buttonElement = getButtonElement();
    const callbackFunction = vi.fn();

    onFocusIn(buttonElement, callbackFunction);

    getOutsideButtonElement().focus();
    expect(callbackFunction).not.toHaveBeenCalled();

    buttonElement.focus();
    expect(callbackFunction).toHaveBeenCalledTimes(1);

    getInsideButtonElement().focus();
    expect(callbackFunction).toHaveBeenCalledTimes(2);
  });
});
