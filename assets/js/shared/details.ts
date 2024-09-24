import { onFocusOut, onKeyDown } from '@utils';

export const detailsComponents = () => {
  let abortController: AbortController | null = null;
  const temporaryAbortControllers = new Set<AbortController>();

  const initialize = () => {
    cleanup();

    const detailElements = document.querySelectorAll<HTMLDetailsElement>('.js-details');
    if (!detailElements.length) {
      return;
    }

    abortController = new AbortController();

    detailElements.forEach((detailElement) => {
      const summaryElement = detailElement.querySelector('summary');
      detailElement.addEventListener('toggle', (toggleEvent) => {
        addFunctionality(toggleEvent, summaryElement);
      }, { signal: abortController?.signal });
    });
  };

  const addFunctionality = (toggleEvent: Event, summaryElement: HTMLElement | null) => {
    const { target } = toggleEvent;

    if (!(target instanceof HTMLDetailsElement)) {
      return;
    }

    if (!target.open) {
      return;
    }

    const temporaryAbortController = new AbortController();
    temporaryAbortControllers.add(temporaryAbortController);

    const hideExpandedContent = () => {
      target.removeAttribute('open');
    };

    const abortTemporaryEventListeners = () => {
      temporaryAbortController.abort();
      temporaryAbortControllers.delete(temporaryAbortController);
    };

    document.addEventListener('click', (event) => {
      if (target.contains(event.target as HTMLElement)) {
        return;
      }

      hideExpandedContent();
      abortTemporaryEventListeners();
    }, { signal: temporaryAbortController.signal });

    onFocusOut(target, () => {
      hideExpandedContent();
      abortTemporaryEventListeners();
    }, { signal: temporaryAbortController.signal });

    onKeyDown('Escape', () => {
      hideExpandedContent();
      abortTemporaryEventListeners();
      summaryElement?.focus();
    }, { signal: temporaryAbortController.signal });
  };

  const cleanup = () => {
    if (abortController) {
      abortController.abort();
    }

    temporaryAbortControllers.forEach((temporaryAbortController) => {
      temporaryAbortController.abort();
    });
    temporaryAbortControllers.clear();
  };

  return {
    cleanup,
    initialize,
  };
};
