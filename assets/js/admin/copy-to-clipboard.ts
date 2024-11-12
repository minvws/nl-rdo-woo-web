import { hideElement, showElement } from '@utils';

export const copyToClipboard = () => {
  let abortController: AbortController;
  const timeoutIds = new Set<NodeJS.Timeout>();

  const initialize = () => {
    cleanup();

    const copyToClipboardElements = document.getElementsByClassName(
      'js-copy-to-clipboard',
    ) as HTMLCollectionOf<HTMLElement>;
    if (!copyToClipboardElements.length) {
      return;
    }

    abortController = new AbortController();
    Array.from(copyToClipboardElements).forEach(addBehavior);
  };

  const addBehavior = (copyToClipboardElement: HTMLElement) => {
    const copyIconElement =
      copyToClipboardElement.querySelector('.js-copy-icon');
    const succesIconElement =
      copyToClipboardElement.querySelector('.js-success-icon');

    copyToClipboardElement.addEventListener(
      'click',
      async () => {
        const textToCopy = copyToClipboardElement.dataset.copyToClipboard;

        await navigator.clipboard.writeText(textToCopy || '');

        hideElement(copyIconElement);
        showElement(succesIconElement);

        const timeoutId = setTimeout(() => {
          hideElement(succesIconElement);
          showElement(copyIconElement);
          cleanupTimeoutId(timeoutId);
        }, 2000);

        timeoutIds.add(timeoutId);
      },
      { signal: abortController?.signal },
    );
  };

  const cleanup = () => {
    if (abortController) {
      abortController.abort();
    }

    timeoutIds.forEach(cleanupTimeoutId);
  };

  const cleanupTimeoutId = (timeoutId: NodeJS.Timeout) => {
    timeoutIds.delete(timeoutId);
    clearTimeout(timeoutId);
  };

  return {
    cleanup,
    initialize,
  };
};
