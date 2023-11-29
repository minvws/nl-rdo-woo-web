export const toggleDialog = () => {
  let abortController: AbortController;

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
          dialogTarget.showModal();
          return;
        }

        dialogTarget.close();
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
