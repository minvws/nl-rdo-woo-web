export const toggleDialog = () => {
  let abortController: AbortController;
  let activeElement: HTMLElement | null;

  const initialize = () => {
    cleanup();

    const dialogToggleElements: NodeListOf<HTMLButtonElement> = document.querySelectorAll('.js-dialog-toggle');

    if (dialogToggleElements.length === 0) {
      return;
    }

    abortController = new AbortController();
    dialogToggleElements.forEach((dialogToggleElement) => {
      dialogToggleElement.addEventListener('click', () => {
        const { dialogAction, dialogFor } = dialogToggleElement.dataset;
        const dialogTarget = document.getElementById(dialogFor as string) as HTMLDialogElement;
        if (!dialogTarget) {
          return;
        }

        if (dialogAction === 'open') {
          activeElement = document.activeElement as HTMLElement;
          dialogTarget.showModal();
          return;
        }

        dialogTarget.close();
        activeElement?.focus();
      }, { signal: abortController.signal! });
    });
  };

  const cleanup = () => {
    activeElement = null;
    if (abortController) {
      abortController.abort();
    }
  };

  return {
    cleanup,
    initialize,
  };
};
