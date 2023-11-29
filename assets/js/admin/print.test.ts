import { beforeEach, describe, expect, test } from '@jest/globals';
import { getWindow } from '../utils';
import { printPage } from './print';

jest.mock('../utils');

describe('the "printPage" function', () => {
  const getPrintPageButton = () => document.querySelector('.js-print-page') as HTMLElement;

  let cleanup: () => void;
  let initialize: () => void;

  beforeEach(() => {
    document.body.innerHTML = `
      <button class="js-print-page">Print this page</button>
    `;

    ({ cleanup, initialize } = printPage());
    initialize();
  });

  afterEach(() => {
    cleanup();
  });

  test('should print the page when clicking a button with the "js-print-page" class name', () => {
    expect(getWindow().print).not.toHaveBeenCalled();

    getPrintPageButton()?.click();
    expect(getWindow().print).toHaveBeenCalledTimes(1);
  });

  test('should remove event listeners when the "cleanup" function is invoked', () => {
    cleanup();

    getPrintPageButton()?.click();
    expect(getWindow().print).not.toHaveBeenCalled();
  });
});
