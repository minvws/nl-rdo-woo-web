import { onDomReady } from '@minvws/manon/utils';
import { debounce } from 'lodash';

onDomReady(function () {
    const searchFieldElement = document.getElementById('search-field');
    const searchSuggestionsElement = document.getElementById('js-search-suggestions');

    if (!searchFieldElement || !searchSuggestionsElement) {
        return;
    }

    const HIDDEN_ATTRIBUTE = 'hidden';
    const searchFormElement = searchFieldElement.closest('form');

    const hideSuggestions = () => {
        searchSuggestionsElement.setAttribute(HIDDEN_ATTRIBUTE, 'hidden');
    };

    const revealSuggestions = () => {
        const abortController = new AbortController();
        searchSuggestionsElement.removeAttribute(HIDDEN_ATTRIBUTE);

        document.addEventListener('click', (event) => {
            if (!searchFormElement.contains(event.target)) {
                hideSuggestions();
                abortController.abort();
            }
        }, { signal: abortController.signal });

        document.addEventListener('focusin', (event) => {
            if (!searchFormElement.contains(event.target)) {
                hideSuggestions();
                abortController.abort();
            }
        }, { signal: abortController.signal });
    };

    const hideOrRevealSuggestions = () => {
        const hasSuggestions = searchSuggestionsElement.childNodes.length > 0;
        if (!hasSuggestions) {
            hideSuggestions();
            return;
        }

        if (hasSuggestions && searchSuggestionsElement.hasAttribute(HIDDEN_ATTRIBUTE)) {
            revealSuggestions();
            return;
        }
    };

    const fetchAndUpdateResults = (searchQuery) => {
        if (searchQuery.length < 2) {
            return;
        }

        fetch(`/_result_minimalistic?q=${encodeURIComponent(searchQuery)}&size=4`)
            .then((response) => response.text())
            .then((html) => {
                searchSuggestionsElement.innerHTML = html;
                hideOrRevealSuggestions();
            })
            ;
    };

    searchFieldElement.addEventListener('input', debounce((event) => {
        fetchAndUpdateResults(searchFieldElement.value);
    }, 400));

    searchFieldElement.addEventListener('focus', (event) => {
        hideOrRevealSuggestions();
    });

});
