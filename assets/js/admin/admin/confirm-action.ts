type Actions = Record<string, string>;

export const confirmAction = () => {
  let abortController: AbortController;

  const initialize = () => {
    abortController = new AbortController();

    document
      .querySelectorAll<HTMLDivElement>('.js-confirm-action')
      .forEach((confirmActionElement) => {
        addBehavior(
          confirmActionElement.querySelector<HTMLFormElement>('form'),
          [
            ...confirmActionElement.querySelectorAll<HTMLDivElement>(
              '.js-action',
            ),
          ].reduce<Actions>(
            (accumulated, actionElement) => ({
              ...accumulated,
              [actionElement.dataset.key ?? '']:
                actionElement.dataset.confirmation ?? '',
            }),
            {},
          ),
        );
      });
  };

  const addBehavior = (
    formElement: HTMLFormElement | null,
    actions: Actions = {},
  ) => {
    if (!formElement) {
      return;
    }

    if (Object.keys(actions).length === 0) {
      return;
    }

    const selectElement =
      formElement.querySelector<HTMLSelectElement>('select');

    if (!selectElement) {
      return;
    }

    formElement.addEventListener('submit', (event: SubmitEvent) => {
      const confirmation = actions[selectElement.value];
      if (!confirmation) {
        return;
      }

      event.preventDefault();
      if (confirm(confirmation)) {
        formElement.submit();
      }
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
