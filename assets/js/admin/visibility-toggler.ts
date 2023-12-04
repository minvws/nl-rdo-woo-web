import { hideElement, isElementHidden, showElement } from '@utils';

export const visibilityToggler = () => {
  let abortController: AbortController;

  const initialize = () => {
    cleanup();

    const visibilityTogglerElements: NodeListOf<HTMLButtonElement> = document.querySelectorAll('.js-visibility-toggler');

    if (visibilityTogglerElements.length === 0) {
      return;
    }

    abortController = new AbortController();
    visibilityTogglerElements.forEach((triggeringElement) => {
      const { selector } = triggeringElement.dataset;
      const selectorElement = document.getElementById(selector as string) as HTMLDialogElement;
      if (!selectorElement) {
        return;
      }

      triggeringElement.addEventListener('click', () => {
        if (isElementHidden(selectorElement)) {
          showElement(selectorElement);
          return;
        }

        hideElement(selectorElement);
      }, { signal: abortController.signal! });
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
