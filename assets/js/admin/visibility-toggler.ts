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
    visibilityTogglerElements.forEach((visibilityTogglerElement) => {
      const idOfElementToToggle = visibilityTogglerElement.getAttribute('aria-controls') || '';
      const elementToToggle = document.getElementById(idOfElementToToggle);
      if (!elementToToggle) {
        return;
      }

      visibilityTogglerElement.addEventListener('click', () => {
        if (isElementHidden(elementToToggle)) {
          showElement(elementToToggle);
          visibilityTogglerElement.setAttribute('aria-expanded', 'true');
          return;
        }

        hideElement(elementToToggle);
        visibilityTogglerElement.setAttribute('aria-expanded', 'false');
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
