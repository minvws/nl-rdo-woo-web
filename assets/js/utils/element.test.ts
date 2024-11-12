import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import {
  collapseElement,
  expandElement,
  hideElement,
  isElementHidden,
  isElementVisible,
  showElement,
} from './element';

let isAnimationDisabled: boolean;

vi.mock('./animation', () => ({
  isAnimationDisabled: vi.fn(() => isAnimationDisabled),
}));

describe('The element utility functions', () => {
  let element: HTMLDivElement;

  const createElement = () => {
    const newElement = document.createElement('div');
    vi.spyOn(newElement, 'scrollHeight', 'get').mockReturnValue(1234);
    return newElement;
  };

  const dispatchTransitionEndEvent = (onElement: HTMLElement) => {
    onElement.dispatchEvent(new Event('transitionend'));
  };

  beforeEach(() => {
    vi.useFakeTimers();

    isAnimationDisabled = true;
    element = createElement();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('the "hideElement" function', () => {
    test('hides the provided element by adding the "hidden" class name to the element', () => {
      hideElement(element);
      expect(element.classList.contains('hidden')).toBe(true);
    });
  });

  describe('the "isElementHidden" function', () => {
    test('should return true when the provided element contains the class name "hidden"', () => {
      expect(isElementHidden(element)).toBe(false);

      hideElement(element);
      expect(isElementHidden(element)).toBe(true);
    });
  });

  describe('the "isElementVisible" function', () => {
    test('should return true when the provided element does not contain the class name "hidden"', () => {
      expect(isElementVisible(element)).toBe(true);

      hideElement(element);
      expect(isElementVisible(element)).toBe(false);
    });
  });

  describe('the "showElement" function', () => {
    test('shows the provided element by removing the "hidden" class name from the element', () => {
      element.classList.add('hidden');

      showElement(element);
      expect(element.classList.contains('hidden')).toBe(false);
    });
  });

  describe('the "collapseElement" function', () => {
    beforeEach(() => {
      isAnimationDisabled = false;
    });

    test('should immediately hide the provided element when the user disabled animations', () => {
      isAnimationDisabled = true;
      expect(isElementHidden(element)).toBe(false);
      collapseElement(element);
      expect(isElementHidden(element)).toBe(true);
    });

    test('should give the element a style of `height: 0, overflow: hidden` if the element should collapse immediately', () => {
      collapseElement(element, false);
      expect(element.style.height).toBe('0px');
      expect(element.style.overflow).toBe('hidden');
    });

    describe('when the element should collapse with an animation', () => {
      describe('first, it', () => {
        test(`should give the element its original height and overflow hidden as style (1), some transition class names (2) and mark the
          element as collapsing (3)`, () => {
          collapseElement(element);

          // (1)
          expect(element.style.height).toBe('1234px');
          expect(element.style.overflow).toBe('hidden');

          // (2)
          expect(element.classList.contains('transition-[height]')).toBe(true);
          expect(
            element.classList.contains('motion-reduce:transition-none'),
          ).toBe(true);

          // (3)
          expect(element.getAttribute('data-is-collapsing')).not.toBeNull();
        });
      });

      describe('then, it', () => {
        test('should give the element a height of 0', () => {
          collapseElement(element);

          vi.advanceTimersByTime(1);

          expect(element.style.height).toBe('0px');
        });
      });

      describe('finally, when collapsed, it', () => {
        test('should mark the element as no longer collapsing', () => {
          collapseElement(element);
          dispatchTransitionEndEvent(element);

          expect(element.getAttribute('data-is-collapsing')).toBeNull();
        });

        test('should hide the element', () => {
          collapseElement(element);

          expect(isElementHidden(element)).toBe(false);
          dispatchTransitionEndEvent(element);
          expect(isElementHidden(element)).toBe(true);
        });
      });
    });
  });

  describe('the "expandElement" function', () => {
    beforeEach(() => {
      isAnimationDisabled = false;
      hideElement(element);
    });

    test('should immediately show the provided element when the user disabled animations', () => {
      isAnimationDisabled = true;
      expect(isElementHidden(element)).toBe(true);
      expandElement(element);
      expect(isElementHidden(element)).toBe(false);
    });

    test('should give the element its original height if the element should expand immediately', () => {
      expandElement(element, false);
      expect(element.style.height).toBe('auto');
      expect(element.style.overflow).toBe('');
    });

    describe('when the element should collapse with an animation', () => {
      describe('first, it', () => {
        test(`should show the element (1), give the element its original height and overflow hidden as style (2), some transition
          class names (3) and mark the element as expanding (4)`, () => {
          expandElement(element);

          // (1)
          expect(isElementVisible(element)).toBe(true);

          // (2)
          expect(element.style.height).toBe('1234px');
          expect(element.style.overflow).toBe('hidden');

          // (3)
          expect(element.classList.contains('transition-[height]')).toBe(true);
          expect(
            element.classList.contains('motion-reduce:transition-none'),
          ).toBe(true);

          // (4)
          expect(element.getAttribute('data-is-expanding')).not.toBeNull();
        });
      });

      describe('finally, when expanded, it', () => {
        test('should mark the element as no longer expanding', () => {
          expandElement(element);
          dispatchTransitionEndEvent(element);

          expect(element.getAttribute('data-is-expanding')).toBeNull();
        });

        test('should show the element', () => {
          expect(isElementVisible(element)).toBe(false);

          expandElement(element);
          dispatchTransitionEndEvent(element);

          expect(isElementVisible(element)).toBe(true);
        });
      });
    });
  });
});
