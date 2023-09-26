import { onKeyDown } from '../utils/index.js';

export const detailsComponents = () => {
    let abortController = null;
    const temporaryAbortControllers = new Set();

    const initialize = () => {
        removeEventListeners();

        const detailElements = document.querySelectorAll('.js-details');
            if (!detailElements.length) {
                return;
            }

            abortController = new AbortController();

            detailElements.forEach((detailElement) => {
                detailElement.addEventListener('toggle', (event) => {
                    const { target } = event;

                    if (!target.open) {
                        return;
                    }

                    const temporaryAbortController = new AbortController();
                    temporaryAbortControllers.add(temporaryAbortController);

                    const hideExpandedContent = () => {
                        target.removeAttribute('open');
                    }

                    const abortEventListeners = () => {
                        temporaryAbortController.abort();
                        temporaryAbortControllers.delete(temporaryAbortController);
                    }

                    document.addEventListener('click', (event) => {
                        if (!target.contains(event.target)) {
                            hideExpandedContent();
                            abortEventListeners();
                        }
                    }, { signal: temporaryAbortController.signal });

                    document.addEventListener('focusin', (event) => {
                        if (!target.contains(event.target)) {
                            hideExpandedContent();
                            abortEventListeners();
                        }
                    }, { signal: temporaryAbortController.signal });

                    onKeyDown('Escape', () => {
                        hideExpandedContent();
                        abortEventListeners();
                    }, { signal: temporaryAbortController.signal });

                }, { signal: abortController.signal });
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
    };

    return {
        initialize,
        removeEventListeners,
    }
};
