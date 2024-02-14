import { debounce, hideElement, isElementHidden, onFocusOut, onKeyDown, showElement } from '@utils';

export const searchPreviews = () => {
  let abortControllerMain: AbortController | null = null;
  let abortControllerTemporary: AbortController | null = null;

  let addExternalFunctionality: (searchResultsElement: HTMLElement, inputElement: HTMLInputElement) => void;
  let formElement: HTMLFormElement | null = null;
  let iconMagnifierElement: HTMLSpanElement | null = null;
  let iconCrossElement: HTMLButtonElement | null = null;
  let inputElement: HTMLInputElement | null = null;
  let overlayElement: HTMLDivElement | null = null;
  let placeholderElement: HTMLDivElement | null = null;
  let removeExternalFunctionality: () => void;

  const initialize = (
    formElementId: string,
    addExternalFunctionalityFn: (searchResultsElement: HTMLElement, inputElement: HTMLInputElement) => void = () => {},
    removeExternalFunctionalityFn: () => void = () => {},
  ) => {
    addExternalFunctionality = addExternalFunctionalityFn;
    removeExternalFunctionality = removeExternalFunctionalityFn;

    formElement = document.getElementById(formElementId) as HTMLFormElement;
    if (!formElement) {
      return;
    }

    iconCrossElement = formElement.querySelector('.js-icon-cross');
    iconMagnifierElement = formElement.querySelector('.js-icon-magnifier');
    inputElement = formElement.querySelector('.js-input');
    overlayElement = formElement.querySelector('.js-overlay');
    placeholderElement = formElement.querySelector('.js-placeholder');

    addMainEventListeners();
  };

  const addMainEventListeners = () => {
    if (!inputElement) {
      return;
    }

    abortControllerMain = new AbortController();

    inputElement.addEventListener('input', debounce((event) => {
      const { value: query } = event.target;

      switchIconsVisibility(query);

      if (query.length < 3) {
        hide();
        return;
      }

      fetchAndUpdateResults(query);
    }, 250), { signal: abortControllerMain.signal });

    inputElement.addEventListener('focus', hideOrShow, { signal: abortControllerMain.signal });

    iconCrossElement?.addEventListener('click', () => {
      inputElement!.value = '';
      inputElement!.focus();
      switchIconsVisibility('');
      hide();
      setPlaceholderContent('');
    });
  };

  const addTemporaryFunctionality = () => {
    if (abortControllerTemporary) {
      // Functionality is already added.
      return;
    }

    abortControllerTemporary = new AbortController();

    addExternalFunctionality(placeholderElement as HTMLElement, inputElement as HTMLInputElement);
    addTemporaryEventListeners();
  };

  const removeTemporaryFunctionality = () => {
    if (!abortControllerTemporary) {
      // Functionality is already removed.
      return;
    }

    cleanupTemporaryEventListeners();
    removeExternalFunctionality();
  };

  const addTemporaryEventListeners = () => {
    document.addEventListener('click', (event) => {
      const { target } = event;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      if (!formElement!.contains(target)) {
        hide();
      }
    }, { signal: abortControllerTemporary?.signal });

    onFocusOut(formElement!, () => {
      hide();
    }, { signal: abortControllerTemporary?.signal });

    onKeyDown('Escape', () => {
      inputElement?.focus();
      hide();
    }, { signal: abortControllerTemporary?.signal });
  };

  const hide = () => {
    hideElement(overlayElement);
    hideElement(placeholderElement);

    inputElement?.setAttribute('aria-expanded', 'false');

    removeTemporaryFunctionality();
  };

  const show = () => {
    showElement(overlayElement);
    showElement(placeholderElement);

    inputElement?.setAttribute('aria-expanded', 'true');

    addTemporaryFunctionality();
  };

  const hideOrShow = () => {
    const hasSuggestions = (placeholderElement?.textContent || '').trim().length > 0;
    if (!hasSuggestions) {
      hide();
      return;
    }

    if (hasSuggestions && isElementHidden(placeholderElement)) {
      show();
    }
  };

  const fetchAndUpdateResults = async (query: string) => {
    const { endpoint = '' } = formElement?.dataset || {};
    const response = await fetch(endpoint, {
      method: 'POST',
      body: JSON.stringify({ q: query }),
      headers: {
        'Content-Type': 'application/json',
      },
    });

    const { results } = await response.json();

    removeTemporaryFunctionality();
    setPlaceholderContent(JSON.parse(results));
    addTemporaryFunctionality();

    hideOrShow();
  };

  const switchIconsVisibility = (query: string) => {
    if (query.length > 0) {
      hideElement(iconMagnifierElement);
      showElement(iconCrossElement);
      return;
    }

    hideElement(iconCrossElement);
    showElement(iconMagnifierElement);
  };

  const setPlaceholderContent = (content: string) => {
    if (!placeholderElement) {
      return;
    }

    placeholderElement.innerHTML = content;
  };

  const cleanup = () => {
    cleanupMainEventListeners();
    removeTemporaryFunctionality();
  };

  const abortController = (controller: AbortController | null) => {
    if (controller) {
      controller.abort();
    }
  };

  const cleanupMainEventListeners = () => {
    abortController(abortControllerMain);
  };

  const cleanupTemporaryEventListeners = () => {
    abortController(abortControllerTemporary);
    abortControllerTemporary = null;
  };

  return {
    cleanup,
    initialize,
  };
};
