import {
  collapseElement,
  expandElement,
  hideElement,
  showElement,
} from '../utils';

export const mainNav = () => {
  let abortController: AbortController;
  let expandableElement: HTMLElement;
  let toggleButtonElement: HTMLElement | null;

  const initialize = () => {
    toggleButtonElement = document.getElementById('js-main-nav-toggle');
    if (!toggleButtonElement) {
      return;
    }

    expandableElement = document.getElementById(
      toggleButtonElement.getAttribute('aria-controls') || '',
    ) as HTMLElement;
    if (!expandableElement) {
      return;
    }

    collapseElement(expandableElement, false);

    abortController = new AbortController();
    toggleButtonElement.addEventListener(
      'click',
      () => {
        if (isExpanded()) {
          collapseExpandableElement();
          return;
        }

        expandExpandableElement();
      },
      { signal: abortController.signal },
    );
  };

  const collapseExpandableElement = () => {
    collapseElement(expandableElement);

    hideElement(getExpandedIconElement());
    showElement(getCollapsedIconElement());

    toggleButtonElement?.setAttribute('aria-expanded', 'false');
  };

  const expandExpandableElement = () => {
    expandElement(expandableElement);

    hideElement(getCollapsedIconElement());
    showElement(getExpandedIconElement());

    toggleButtonElement?.setAttribute('aria-expanded', 'true');
  };

  const getCollapsedIconElement = () =>
    toggleButtonElement?.querySelector('.js-icon-collapsed') ?? null;
  const getExpandedIconElement = () =>
    toggleButtonElement?.querySelector('.js-icon-expanded') ?? null;
  const isExpanded = () =>
    toggleButtonElement?.getAttribute('aria-expanded') === 'true';

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
