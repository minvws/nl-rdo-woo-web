export const resetFocus = () => {
  let abortController: AbortController;

  const ATTRIBUTE_ARIA_DESCRIBED_BY = 'aria-describedby';

  const initialize = (previousActiveElement?: HTMLElement) => {
    cleanup();

    if (!previousActiveElement) {
      return;
    }

    const newActiveElement = findElement(previousActiveElement);
    if (!newActiveElement) {
      document.getElementById('js-number-of-search-results')?.focus();
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
  };

  const findElement = (previousActiveElement: HTMLElement): HTMLElement | null => {
    const { id } = previousActiveElement;
    if (id) {
      return document.getElementById(id) as HTMLElement | null;
    }

    const ariaControls = previousActiveElement.getAttribute('aria-controls');
    if (ariaControls) {
      return document.querySelector(`[aria-controls="${ariaControls}"]`);
    }

    const { name, value } = previousActiveElement as HTMLInputElement;
    if (name && value) {
      return Array.from(
        document.getElementsByName(name) as NodeListOf<HTMLInputElement>,
      ).find((element) => element.value === value) || null;
    }

    return null;
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
