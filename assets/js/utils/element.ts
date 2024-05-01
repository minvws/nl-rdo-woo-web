import { isAnimationDisabled } from './animation';

const HIDDEN_CLASS_NAME = 'hidden';
const TRANSITION_CLASSNAMES = ['transition-[height]', 'motion-reduce:transition-none'];
const IS_COLLAPSING_ATTRIBUTE = 'data-is-collapsing';
const IS_EXPANDING_ATTRIBUTE = 'data-is-expanding';

export const hideElement = (element: Element | null) => {
  element?.classList.add(HIDDEN_CLASS_NAME);
};

export const isElementHidden = (element: Element | null) => Boolean(element?.classList.contains(HIDDEN_CLASS_NAME));

export const showElement = (element: Element | null) => {
  element?.classList.remove(HIDDEN_CLASS_NAME);
};

export const collapseElement = (element: HTMLElement, withAnimation = true) => {
  if (isAnimationDisabled()) {
    hideElement(element);
    return;
  }

  if (!withAnimation) {
    hideElement(element);
    element.style.height = '0px';
    element.style.overflow = 'hidden';
    return;
  }

  element.classList.add(...TRANSITION_CLASSNAMES);
  markElementAsCollapsing(element);

  element.style.overflow = 'hidden';
  element.style.height = `${element.scrollHeight}px`;

  setTimeout(() => {
    element.style.height = '0px';
  }, 0);

  element.addEventListener('transitionend', () => {
    unmarkElementAsCollapsing(element);

    if (isElementMarkedAsExpanding(element)) {
      // The user clicked fast enough to expand the group again before the animation ended.
      return;
    }

    hideElement(element);
  }, { once: true });
};

export const expandElement = (element: HTMLElement, withAnimation = true) => {
  if (isAnimationDisabled()) {
    showElement(element);
    return;
  }

  if (!withAnimation) {
    showElement(element);
    element.style.height = 'auto';
    element.style.overflow = '';
    return;
  }

  element.classList.add(...TRANSITION_CLASSNAMES);
  showElement(element);
  markElementAsExpanding(element);

  element.style.height = `${element.scrollHeight}px`;
  element.style.overflow = 'hidden';

  element.addEventListener('transitionend', () => {
    unmarkElementAsExpanding(element);

    if (isElementMarkedAsCollapsing(element)) {
      // The user clicked fast enough to collapse the group again before the animation ended.
      return;
    }

    element.style.height = '';
    element.style.overflow = '';
  }, { once: true });
};

const markElementAsCollapsing = (element: HTMLElement) => markElementAs(element, IS_COLLAPSING_ATTRIBUTE);
const unmarkElementAsCollapsing = (element: HTMLElement) => unmarkElementAs(element, IS_COLLAPSING_ATTRIBUTE);
const isElementMarkedAsCollapsing = (element: HTMLElement) => isElementMarkedAs(element, IS_COLLAPSING_ATTRIBUTE);
const markElementAsExpanding = (element: HTMLElement) => markElementAs(element, IS_EXPANDING_ATTRIBUTE);
const unmarkElementAsExpanding = (element: HTMLElement) => unmarkElementAs(element, IS_EXPANDING_ATTRIBUTE);
const isElementMarkedAsExpanding = (element: HTMLElement) => isElementMarkedAs(element, IS_EXPANDING_ATTRIBUTE);
const markElementAs = (element: HTMLElement, attribute: string) => element.setAttribute(attribute, '');
const unmarkElementAs = (element: HTMLElement, attribute: string) => element.removeAttribute(attribute);
const isElementMarkedAs = (element: HTMLElement, attribute: string) => element.hasAttribute(attribute);
