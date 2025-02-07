export const isFocusWithinElement = (element: HTMLElement | null) =>
  Boolean(element?.contains(document.activeElement));
