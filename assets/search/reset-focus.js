export const resetFocus = () => {
    let abortController = null;

    const ATTRIBUTE_ARIA_DESCRIBED_BY = 'aria-describedby';

    const initialize = (previousActiveElement) => {
        if (abortController) {
            abortController.abort();
        }

        const newActiveElement = findElement(previousActiveElement);
        if (!newActiveElement) {
            return;
        }

        newActiveElement.focus();
        abortController = new AbortController();

        /**
         * To make the search results (working with ajax and updating html) accessible, we need to add an aria-describedby attribute to the
         * active element.
         * It results in a screen reader announcing the number of search results. However, it should announce it only once. That's why we
         * need to remove the aria-describedby attribute when the active element loses focus.
         */

        const originalAriaDescribedBy = newActiveElement.getAttribute(ATTRIBUTE_ARIA_DESCRIBED_BY);
        newActiveElement.setAttribute(ATTRIBUTE_ARIA_DESCRIBED_BY, 'js-number-of-search-results');

        newActiveElement.addEventListener('focusout', () => {
            if (originalAriaDescribedBy) {
                // Reset to original value.
                newActiveElement.setAttribute(ATTRIBUTE_ARIA_DESCRIBED_BY, originalAriaDescribedBy);
                return;
            }

            newActiveElement.removeAttribute(ATTRIBUTE_ARIA_DESCRIBED_BY);
        }, { once: true, signal: abortController.signal });
    }

    const findElement = (previousActiveElement) => {
        if (!previousActiveElement) {
            return;
        }

        const { id } = previousActiveElement;
        if (id) {
            return document.getElementById(id);
        }

        const ariaControls = previousActiveElement.getAttribute('aria-controls');
        if (ariaControls) {
            return document.querySelector(`[aria-controls="${ariaControls}"]`);
        }

        const { name, value } = previousActiveElement;
        if (name && value) {
            return Array.from(document.getElementsByName(name)).find((element) => element.value === value);
        }

        return;
    }

    return {
        initialize,
    };
}
