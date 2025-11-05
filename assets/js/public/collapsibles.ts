import {
  collapseElement,
  expandElement,
  hideElement,
  showElement,
} from '../utils';

export const collapsibles = () => {
  let abortController: AbortController;

  const initialize = () => {
    abortController = new AbortController();

    document
      .querySelectorAll<HTMLButtonElement>('.js-collapsible-toggle')
      .forEach(addBehavior);
  };

  const addBehavior = (collapsibleToggleElement: HTMLButtonElement) => {
    const collapsibleElement = document.getElementById(
      collapsibleToggleElement.getAttribute('aria-controls') ?? '',
    );

    if (!collapsibleElement) {
      return;
    }

    collapseElement(collapsibleElement, false);
    collapsibleElement.classList.remove('js:hidden');

    collapsibleToggleElement.addEventListener(
      'click',
      () => {
        if (collapsibleToggleElement.getAttribute('aria-expanded') === 'true') {
          collapseElement(collapsibleElement);
          setAriaExpanded(collapsibleToggleElement, false);

          hideElement(getExpandedToggleContent(collapsibleToggleElement));
          showElement(getCollapsedToggleContent(collapsibleToggleElement));
        } else {
          expandElement(collapsibleElement);
          setAriaExpanded(collapsibleToggleElement, true);

          hideElement(getCollapsedToggleContent(collapsibleToggleElement));
          showElement(getExpandedToggleContent(collapsibleToggleElement));
        }
      },
      { signal: abortController.signal },
    );
  };

  const getCollapsedToggleContent = (
    collapsibleToggleElement: HTMLButtonElement,
  ) => collapsibleToggleElement.querySelector('.js-is-collapsed');

  const getExpandedToggleContent = (
    collapsibleToggleElement: HTMLButtonElement,
  ) => collapsibleToggleElement.querySelector('.js-is-expanded');

  const setAriaExpanded = (
    collapsibleToggleElement: HTMLButtonElement,
    isExpanded: boolean,
  ) => {
    collapsibleToggleElement.setAttribute(
      'aria-expanded',
      isExpanded ? 'true' : 'false',
    );
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
