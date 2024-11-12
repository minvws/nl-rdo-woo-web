export const manageWidget = () => {
  let abortController: AbortController;
  let removeWidgetAbortController: AbortController;

  const initialize = () => {
    cleanup();

    const widgetsContainer = document.getElementById(
      'js-widgets-container',
    ) as HTMLDivElement;

    // Return as soon as possible.
    if (!widgetsContainer) {
      return;
    }

    const addButton = document.getElementById(
      'js-add-widget',
    ) as HTMLButtonElement;
    const prototype = document.getElementById('js-widget-prototype')!.innerHTML;
    let index = widgetsContainer.querySelectorAll('input').length;

    abortController = new AbortController();
    addRemoveWidgetFunctionality();

    addButton.addEventListener(
      'click',
      () => {
        // Creates a new form row using the prototype.
        const newForm = prototype.replace(/__name__/g, index.toString());

        // Increase for each row to keep the field numbers unique.
        index += 1;

        // Creates a temporary div to hold the new form row.
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = newForm;

        // Appends the new form row to the container.
        widgetsContainer.appendChild(tempDiv.firstElementChild as HTMLElement);

        // Bind remove widget functionality.
        addRemoveWidgetFunctionality();
      },
      { signal: abortController?.signal },
    );
  };

  const addRemoveWidgetFunctionality = () => {
    // First clean up the formal widget event listeners.
    removeWidgetEventListeners();
    removeWidgetAbortController = new AbortController();

    const removeButtons: NodeListOf<HTMLButtonElement> =
      document.querySelectorAll('.js-remove-widget')!;
    removeButtons.forEach((removeButton) => {
      removeButton.addEventListener(
        'click',
        () => {
          // Find the ancestor element to be removed.
          const prefixToRemove = removeButton.closest('.bhr-widget');

          // Removes the clicked prefix from the DOM (after form submission the prefix will be deleted).
          if (prefixToRemove) {
            // Remove the ancestor element
            prefixToRemove.remove();
          }
        },
        { signal: removeWidgetAbortController?.signal },
      );
    });
  };

  const removeWidgetEventListeners = () => {
    if (removeWidgetAbortController) {
      removeWidgetAbortController.abort();
    }
  };

  const cleanup = () => {
    if (abortController) {
      abortController.abort();
    }

    removeWidgetEventListeners();
  };

  return {
    cleanup,
    initialize,
  };
};
