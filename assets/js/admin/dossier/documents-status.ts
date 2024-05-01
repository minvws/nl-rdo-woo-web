import { hideElement, showElement } from '@utils';

export const dossierDocumentsStatus = () => {
  const WAIT_FOR = 3000;

  let abortController: AbortController | null = null;
  let areAllFilesUploaded = false;
  let canNotContinueYetErrorElement: HTMLElement | null = null;
  let placeholderElement: HTMLElement | null = null;
  let timeoutId: NodeJS.Timeout | null = null;
  let uploadsRemainingElement: HTMLElement | null = null;
  let uploadsSectionElement: HTMLElement | null = null;

  const initialize = () => {
    placeholderElement = document.getElementById('js-dossier-documents-status');
    if (!placeholderElement) {
      return;
    }

    canNotContinueYetErrorElement = document.getElementById('js-dossier-documents-can-not-continue');
    uploadsRemainingElement = placeholderElement.querySelector('.js-uploads-remaining');
    uploadsSectionElement = placeholderElement.querySelector('.js-upload-section');

    abortController = new AbortController();
    updateStatus(placeholderElement.dataset.endpoint || '');
    addToNextStepBehaviour();
  };

  const updateStatus = async (endpoint: string) => {
    const response = await fetch(endpoint, { signal: abortController?.signal });
    const { completed, uploadsProcessingContent, uploadsRemainingContent } = await response.json();

    areAllFilesUploaded = completed;

    const uploadsProcessingElement = placeholderElement?.querySelector('.js-uploads-processing');
    if (uploadsProcessingElement) {
      uploadsProcessingElement.innerHTML = uploadsProcessingContent;
    }

    if (uploadsRemainingElement) {
      uploadsRemainingElement.innerHTML = uploadsRemainingContent;
    }

    if (areAllFilesUploaded) {
      hideElement(uploadsRemainingElement);
      hideElement(uploadsSectionElement);

      hideElement(canNotContinueYetErrorElement);
      showElement(document.getElementById('js-dossier-documents-completed'));
      return;
    }

    timeoutId = setTimeout(() => {
      cleanupTimeout();
      updateStatus(endpoint);
    }, WAIT_FOR);
  };

  const addToNextStepBehaviour = () => {
    if (areAllFilesUploaded || !canNotContinueYetErrorElement) {
      return;
    }

    const toNextStepButton = document.getElementById('js-dossier-documents-next-step');
    if (!toNextStepButton) {
      return;
    }

    toNextStepButton.addEventListener('click', (event) => {
      if (areAllFilesUploaded) {
        return;
      }

      event.preventDefault();
      showElement(canNotContinueYetErrorElement);
    });
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

  return {
    initialize,
    cleanup,
  };
};
