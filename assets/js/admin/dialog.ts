export const toggleDialog = () => {
  let abortController: AbortController;

  const initialize = () => {
    cleanup();

    abortController = new AbortController();
    document
      .querySelectorAll<HTMLButtonElement>('.js-dialog-toggle')
      .forEach((dialogToggleElement) => {
        dialogToggleElement.addEventListener(
          'click',
          () => {
            const { dialogAction, dialogFor } = dialogToggleElement.dataset;
            const dialogTarget = document.getElementById(
              dialogFor as string,
            ) as HTMLDialogElement;

            if (!dialogTarget) {
              return;
            }

            if (dialogAction === 'open') {
              dialogTarget.showModal();
              return;
            }

            dialogTarget.close();
          },
          { signal: abortController.signal! },
        );
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
