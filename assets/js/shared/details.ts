import { onFocusOut, onKeyDown } from '@utils';

export const detailsComponents = () => {
  let abortController: AbortController | null = null;
  const temporaryAbortControllers = new Set<AbortController>();

  const initialize = () => {
    cleanup();

    const detailElements = document.querySelectorAll('.js-details') as NodeListOf<HTMLDetailsElement>;
    if (!detailElements.length) {
      return;
    }

    abortController = new AbortController();

    detailElements.forEach((detailElement) => {
      const summaryElement = detailElement.querySelector('summary');

      detailElement.addEventListener('toggle', (toggleEvent) => {
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
          if (!target.contains(event.target as HTMLElement)) {
            hideExpandedContent();
            abortTemporaryEventListeners();
          }
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
      }, { signal: abortController?.signal });
    });
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
