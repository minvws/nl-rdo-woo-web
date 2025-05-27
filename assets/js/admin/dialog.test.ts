import {
  afterEach,
  beforeEach,
  describe,
  test,
  type Mock,
  vi,
  expect,
} from 'vitest';
import { toggleDialog } from './dialog';

describe('The functionality regarding toggling a dialog', () => {
  let cleanup: () => void;
  let initialize: () => void;

  let closeDialogSpy: Mock;
  let showDialogSpy: Mock;

  beforeEach(() => {
    document.body.innerHTML = `
      <button
        class="js-dialog-toggle"
        data-dialog-action="open"
        data-dialog-for="dialog"
      >Open dialog</button>
      <dialog id="dialog">
        <button
          class="js-dialog-toggle"
          data-dialog-action="close"
          data-dialog-for="dialog"
        >Close dialog</button>
      </dialog>

      <button
        class="js-dialog-toggle"
        data-dialog-action="open"
        data-dialog-for="non-existing-dialog"
      >Open non-existing dialog</button>
    `;

    ({ cleanup, initialize } = toggleDialog());
    initialize();

    closeDialogSpy = vi.fn();
    showDialogSpy = vi.fn();

    HTMLDialogElement.prototype.close = closeDialogSpy;
    HTMLDialogElement.prototype.showModal = showDialogSpy;
  });

  const getButton = (selector: string) =>
    document.querySelector<HTMLButtonElement>(selector);

  const getOpenDialogButton = () =>
    getButton('[data-dialog-for="dialog"][data-dialog-action="open"]');

  const getCloseDialogButton = () =>
    getButton('[data-dialog-for="dialog"][data-dialog-action="close"]');

  const getOpenNonExistingDialogButton = () =>
    getButton(
      '[data-dialog-for="non-existing-dialog"][data-dialog-action="open"]',
    );

  afterEach(() => {
    cleanup();
  });

  test('should open a dialog when the button is clicked', () => {
    expect(showDialogSpy).not.toHaveBeenCalled();

    getOpenDialogButton()?.click();

    expect(showDialogSpy).toHaveBeenCalled();
  });

  test('should close a dialog when the button is clicked', () => {
    expect(closeDialogSpy).not.toHaveBeenCalled();

    getCloseDialogButton()?.click();

    expect(closeDialogSpy).toHaveBeenCalled();
  });

  test('should do nothing when the button refers to a non-existing dialog', () => {
    expect(showDialogSpy).not.toHaveBeenCalled();

    getOpenNonExistingDialogButton()?.click();

    expect(showDialogSpy).not.toHaveBeenCalled();
  });
});
