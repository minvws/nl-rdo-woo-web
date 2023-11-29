const className = 'hidden';

export const hideElement = (element: Element | null) => {
  element?.classList.add(className);
};

export const isElementHidden = (element: Element | null) => Boolean(element?.classList.contains(className));

export const showElement = (element: Element | null) => {
  element?.classList.remove(className);
};
