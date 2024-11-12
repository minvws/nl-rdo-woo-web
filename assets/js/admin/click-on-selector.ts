export const clickOnSelector = () => {
  let abortController: AbortController;

  const initialize = () => {
    cleanup();

    const clickOnSelectorElements: NodeListOf<HTMLButtonElement> =
      document.querySelectorAll('.js-click-on-selector');

    if (clickOnSelectorElements.length === 0) {
      return;
    }

    abortController = new AbortController();
    clickOnSelectorElements.forEach((triggeringElement) => {
      const { selector } = triggeringElement.dataset;
      const selectorElement = document.getElementById(
        selector as string,
      ) as HTMLDialogElement;
      if (!selectorElement) {
        return;
      }

      triggeringElement.addEventListener(
        'click',
        () => {
          selectorElement.click();
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
