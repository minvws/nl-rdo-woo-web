import { hideElement } from '@utils';
import { uploadAreas } from '../upload-areas';

export const dossierInventoryStatus = () => {
  const WAIT_FOR = 3000;

  let abortController: AbortController | null = null;
  let placeholderElement: HTMLElement | null = null;
  let timeoutId: NodeJS.Timeout | null = null;

  const { cleanup: cleanupUploadAreas, initialize: initializeUploadAreas } = uploadAreas();

  const initialize = () => {
    placeholderElement = document.getElementById('js-inventory-status');
    if (!placeholderElement) {
      return;
    }

    abortController = new AbortController();
    updateStatus(placeholderElement.dataset.endpoint || '');
  };

  const updateStatus = async (endpoint: string) => {
    const response = await fetch(endpoint, { signal: abortController?.signal });
    const { content, inventoryStatus } = await response.json();

    if (placeholderElement) {
      placeholderElement.innerHTML = content;
    }

    if (inventoryStatus.hasErrors) {
      // There was an error, so the file upload is displayed now. A form submit will result in a new page load.
      cleanup();

      hideContinueLaterButton();
      initializeUploadAreas();
      return;
    }

    if (inventoryStatus.needsUpdate === false) {
      const { doneUrl } = placeholderElement?.dataset || {};
      if (doneUrl) {
        cleanup();
        window.location.assign(doneUrl);
      }
      return;
    }

    timeoutId = setTimeout(() => {
      cleanupTimeout();
      cleanupUploadAreas();

      updateStatus(endpoint);
    }, WAIT_FOR);
  };

  const cleanupTimeout = () => {
    if (timeoutId) {
      clearTimeout(timeoutId);
      timeoutId = null;
    }
  };

  const cleanup = () => {
    if (abortController) {
      abortController.abort();
      abortController = null;
    }

    cleanupTimeout();
    cleanupUploadAreas();
  };

  const hideContinueLaterButton = () => {
    hideElement(getContinueLaterButton());
  };

  const getContinueLaterButton = (): HTMLElement | null => {
    const wrapperElement = getWrapperElement();
    if (!wrapperElement) {
      return null;
    }

    return wrapperElement.querySelector('.js-inventory-status-continue-later');
  };

  const getWrapperElement = () => placeholderElement?.closest('.js-inventory-status-wrapper');

  return {
    initialize,
    cleanup,
  };
};
