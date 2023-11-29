export const autoSubmitForm = () => {
  let abortController: AbortController;
  let forms: NodeListOf<HTMLFormElement>;

  const initialize = () => {
    forms = document.querySelectorAll('form.js-auto-submit-form');

    if (forms.length === 0) {
      return;
    }

    abortController = new AbortController();

    initializeForms();
  };

  const initializeForms = () => {
    forms.forEach((form) => {
      addSubmitHandler(form);
    });
  };

  const addSubmitHandler = (form: HTMLFormElement) => {
    if (!form.submit) {
      return;
    }

    const formInputs = form.querySelectorAll('input');

    formInputs.forEach((formInput) => {
      formInput.addEventListener('change', () => {
        window.location.hash = form.id ? form.id : '';
        form.submit();
      }, { signal: abortController?.signal });
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
