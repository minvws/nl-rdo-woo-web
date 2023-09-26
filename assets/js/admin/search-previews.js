import { debounce } from 'lodash';
import { clickableRows } from './clickable-row.js';
import { onKeyDown } from '../utils/index.js';

export const searchPreviews = () => {
    let abortController = null;
    const temporaryAbortControllers = new Set();
    const HIDDEN_ATTRIBUTE = 'hidden';

    const { initialize: initializeClickableRows, removeEventListeners: removeClickableRowEventListeners } = clickableRows();

    const initialize = () => {
        removeEventListeners();

        const searchFieldElement = document.querySelector('.js-search-previews-input');
        const searchPreviewsElement = document.getElementById('js-search-previews-placeholder');

        if (!searchFieldElement || !searchPreviewsElement) {
            return;
        }

        abortController = new AbortController();

        searchFieldElement.addEventListener('input', debounce((event) => {
            fetchAndUpdateResults(searchPreviewsElement, searchFieldElement.value);
        }, 400), { signal: abortController.signal });

        searchFieldElement.addEventListener('focus', (event) => {
            hideOrRevealSuggestions(searchPreviewsElement);
        }, { signal: abortController.signal });
    };

    const hideSuggestions = (searchPreviewsElement) => {
        searchPreviewsElement.setAttribute(HIDDEN_ATTRIBUTE, 'hidden');
    };

    const revealSuggestions = (searchPreviewsElement) => {
        searchPreviewsElement.removeAttribute(HIDDEN_ATTRIBUTE);

        const searchFormElement = searchPreviewsElement.closest('form');
        const temporaryAbortController = new AbortController();
        temporaryAbortControllers.add(temporaryAbortController);

        const abortTemporaries = () => {
            temporaryAbortController.abort();
            temporaryAbortControllers.delete(temporaryAbortController);
        };

        document.addEventListener('click', (event) => {
            if (!searchFormElement.contains(event.target)) {
                hideSuggestions(searchPreviewsElement);
                abortTemporaries();
            }
        }, { signal: temporaryAbortController.signal });

        document.addEventListener('focusin', (event) => {
            if (!searchFormElement.contains(event.target)) {
                hideSuggestions(searchPreviewsElement);
                abortTemporaries();
            }
        }, { signal: temporaryAbortController.signal });

        onKeyDown('Escape', () => {
            hideSuggestions(searchPreviewsElement);
            abortTemporaries();
        }, { signal: temporaryAbortController.signal });
    };

    const hideOrRevealSuggestions = (searchPreviewsElement) => {
        const hasSuggestions = searchPreviewsElement.textContent.trim().length > 0;
        if (!hasSuggestions) {
            hideSuggestions(searchPreviewsElement);
            return;
        }

        if (hasSuggestions && searchPreviewsElement.hasAttribute(HIDDEN_ATTRIBUTE)) {
            revealSuggestions(searchPreviewsElement);
            return;
        }
    };

    const fetchAndUpdateResults = (searchPreviewsElement, searchQuery) => {
        if (searchQuery.length < 2) {
            hideSuggestions(searchPreviewsElement);
            return;
        }

        fetch(`/_result_minimalistic?q=${encodeURIComponent(searchQuery)}&size=4`)
            .then((response) => response.text())
            .then((html) => {
                removeClickableRowEventListeners();

                searchPreviewsElement.innerHTML = html;
                hideOrRevealSuggestions(searchPreviewsElement);

                initializeClickableRows();
            });
    };

    const removeEventListeners = () => {
        if (abortController) {
            abortController.abort();
        }

        for (const temporaryAbortController of temporaryAbortControllers) {
            temporaryAbortController.abort();
        }
        temporaryAbortControllers.clear();

        removeClickableRowEventListeners();
    };

    return {
        initialize,
        removeEventListeners,
    }
};
