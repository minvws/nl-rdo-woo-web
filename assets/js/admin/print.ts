import { getWindow } from '../utils';

export const printPage = () => {
  let abortController: AbortController;

  const initialize = () => {
    cleanup();

    const printButtons: NodeListOf<HTMLButtonElement> =
      document.querySelectorAll('.js-print-page');
    if (printButtons.length === 0) {
      return;
    }

    abortController = new AbortController();
    printButtons.forEach((button) => {
      button.addEventListener(
        'click',
        () => {
          getWindow().print();
        },
        { signal: abortController?.signal },
      );
    });
  };

  const cleanup = () => {
    if (abortController) {
      abortController.abort();
    }
  };

  return {
    cleanup,
    initialize,
  };
};
