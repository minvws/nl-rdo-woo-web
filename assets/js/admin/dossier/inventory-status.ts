import { hideElement } from '@utils';

interface InventoryStatusResponse {
  content: string;
  inventoryStatus: {
    hasErrors: boolean;
    needsConfirmation: boolean;
    needsUpdate: boolean;
  };
}

export const dossierInventoryStatus = () => {
  const WAIT_FOR = 3000;
  const MAX_CONSECUTIVE_FAILURES = 5;

  let abortController: AbortController | null = null;
  let consecutiveFailures = 0;
  let placeholderElement: HTMLElement | null = null;
  let timeoutId: NodeJS.Timeout | null = null;

  const initialize = () => {
    placeholderElement = document.getElementById('js-inventory-status');
    if (!placeholderElement) {
      return;
    }

    abortController = new AbortController();
    void updateStatus(placeholderElement.dataset.endpoint || '');
  };

  const scheduleNextUpdate = (endpoint: string) => {
    timeoutId = setTimeout(() => {
      cleanupTimeout();

      void updateStatus(endpoint);
    }, WAIT_FOR);
  };

  const handleRequestFailure = (error: unknown, endpoint: string) => {
    const isAborted =
      error instanceof DOMException && error.name === 'AbortError';
    if (isAborted) {
      return;
    }

    consecutiveFailures += 1;
    if (consecutiveFailures < MAX_CONSECUTIVE_FAILURES) {
      scheduleNextUpdate(endpoint);
    }
  };

  const updateStatus = async (endpoint: string) => {
    let content: string;
    let inventoryStatus: InventoryStatusResponse['inventoryStatus'];

    try {
      const response = await fetch(endpoint, {
        signal: abortController?.signal,
      });
      if (!response.ok) {
        throw new Error(`Unexpected response status ${response.status}`);
      }

      ({ content, inventoryStatus } = await response.json());
    } catch (error) {
      handleRequestFailure(error, endpoint);
      return;
    }

    consecutiveFailures = 0;

    if (placeholderElement) {
      placeholderElement.innerHTML = content;
    }

    if (inventoryStatus.hasErrors || inventoryStatus.needsConfirmation) {
      // A form submit will result in a new page load.
      cleanup();

      hideContinueLaterButton();
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

    scheduleNextUpdate(endpoint);
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
  };

  const hideContinueLaterButton = () =>
    hideElement(getContinueLaterButton() ?? null);

  const getContinueLaterButton = () =>
    getWrapperElement()?.querySelector('.js-inventory-status-continue-later');

  const getWrapperElement = () =>
    placeholderElement?.closest('.js-inventory-status-wrapper');

  return {
    initialize,
    cleanup,
  };
};
