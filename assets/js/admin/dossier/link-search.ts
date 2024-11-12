import { listboxRole } from '../../utils';
import { searchPreviews } from './search-previews';

export const dossierLinkSearch = () => {
  let clickDossierAbortController: AbortController | null;
  let hiddenDossierIdsInputElement: HTMLInputElement;
  let linkDossierAbortController: AbortController | null;
  let selectedDossiersElement: HTMLDivElement;
  let selectedDossiersFallbackElement: HTMLSelectElement;

  const listboxSettings = {
    defaultOptionClassNames: ['bhr-clickable-row'],
    highlightedOptionClassNames: [
      'bhr-clickable-row--with-color',
      'bhr-outline',
    ],
  };

  const { initialize: initializeListboxRole, cleanup: cleanupListboxRole } =
    listboxRole(listboxSettings);
  const {
    initialize: initializeSearchPreviews,
    cleanup: cleanupSearchPreviews,
  } = searchPreviews();

  const hiddenDossierIds = new Set<string>();

  const initialize = () => {
    initializeSearchPreviews(
      'js-link-dossiers-search-form',
      addSearchResultsFunctionality,
      removeSearchResultsFunctionality,
    );

    hiddenDossierIdsInputElement = document.getElementById(
      'js-hidden-dossiers-input',
    ) as HTMLInputElement;
    selectedDossiersElement = document.getElementById(
      'js-selected-dossiers',
    ) as HTMLDivElement;
    selectedDossiersFallbackElement = document.querySelector(
      '.js-select-dossiers-fallback',
    ) as HTMLSelectElement;

    displayPossibleErrors();
    addLinkDossierFunctionality();
  };

  const displayPossibleErrors = () => {
    const errorNotification = selectedDossiersFallbackElement?.closest(
      '.bhr-form-row--invalid',
    ) as HTMLDivElement;
    if (!errorNotification) {
      return;
    }

    const originalErrorElement =
      errorNotification.querySelector('.js-input-errors');
    const copiedErrorElement =
      selectedDossiersElement.querySelector('.js-input-errors');

    if (!originalErrorElement || !copiedErrorElement) {
      return;
    }

    const invalidInputField = document.getElementById(
      'link-dossiers-search-input',
    ) as HTMLInputElement;
    if (!invalidInputField) {
      return;
    }

    copiedErrorElement.outerHTML = originalErrorElement.outerHTML;

    invalidInputField.setAttribute('aria-invalid', 'true');
    const currentAriaDescribedBy =
      invalidInputField.getAttribute('aria-describedby') ?? '';
    invalidInputField.setAttribute(
      'aria-describedby',
      `${currentAriaDescribedBy} ${originalErrorElement.id}`.trim(),
    );

    originalErrorElement.remove();

    selectedDossiersElement.classList.add('bhr-form-row--invalid');
  };

  const addSearchResultsFunctionality = (
    searchResultsElement: HTMLElement,
    inputElement: HTMLInputElement,
  ) => {
    // The search previews functionality will hide the search results when an element outside the search form receives focus.
    // In this case, the search form is included in a dialog element. It will receive focus when leaving the search field.
    // To prevent the search results from hiding in this case, we give the search results element a tabindex of -1.
    searchResultsElement.setAttribute('tabindex', '-1');

    initializeListboxRole(searchResultsElement, inputElement, onOptionSelect);
  };

  const onOptionSelect = (optionElement: HTMLElement) => {
    const dossierId = optionElement.getAttribute('data-dossier-id') as string;
    hiddenDossierIdsInputElement.value = dossierId;
    // Assign values to the associative array using string keys.
    hiddenDossierIds.add(dossierId);
  };

  const addLinkDossierFunctionality = () => {
    linkDossierAbortController = new AbortController();

    const dialogElement = document.getElementById(
      'js-link-dossiers-dialog',
    ) as HTMLDialogElement;
    const linkDossierButtonElement = document.getElementById(
      'js-link-dossier',
    ) as HTMLButtonElement;
    const selectedDossiersListElement = document.getElementById(
      'js-selected-dossiers-list',
    ) as HTMLParagraphElement;

    linkDossierButtonElement?.addEventListener(
      'click',
      () => {
        // Check if the hidden element contains a selected dossier ID.
        if (!hiddenDossierIdsInputElement.value) {
          return;
        }

        // Set all the selected dossier ids on the actual inquiry_link_dossier_form_dossiers select field.
        const { options } = selectedDossiersFallbackElement;
        // Store the selected option titles.
        const optionsText: string[] = [];
        for (let i = 0; i < options.length; i += 1) {
          const option = options[i] as HTMLOptionElement;
          if (hiddenDossierIds.has(option.value)) {
            option.selected = true;
            optionsText.push(option.textContent!);
          }
        }
        // Let's display the selected option titles to the balie user.
        selectedDossiersListElement.innerHTML = `<li>${optionsText.join('</li><li>')}</li>`;
        dialogElement.close();
      },
      { signal: linkDossierAbortController.signal },
    );
  };

  const removeSearchResultsFunctionality = () => {
    clickDossierAbortController?.abort();
    clickDossierAbortController = null;
    cleanupListboxRole();
  };

  const removeLinkDossierFunctionality = () => {
    linkDossierAbortController?.abort();
    linkDossierAbortController = null;
  };

  const cleanup = () => {
    removeLinkDossierFunctionality();
    cleanupSearchPreviews();
  };

  return {
    cleanup,
    initialize,
  };
};
