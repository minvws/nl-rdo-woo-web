export const onFocusIn = (element: HTMLElement, fn: () => void, options?: boolean | AddEventListenerOptions) => {
  document.addEventListener('focusin', (event) => {
    const { target } = event;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    if (!element.contains(target)) {
      return;
    }

    fn();
  }, options);
};
