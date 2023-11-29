import { hideElement, showElement } from '@utils';

export interface UploadVisual {
  adjustToCoverWholePage: () => void;
  cleanup: () => void;
  slideInUp: () => void;
  slideOutDown: () => void;
  slideOutUp: () => void;
}

export const initializeUploadVisual = (element: HTMLElement): UploadVisual => {
  const TRANSITION_DURATION = 150;

  const dotElement = element.querySelector('.js-upload-visual-dot') as HTMLElement;
  let slideInTimeoutId: NodeJS.Timeout | undefined;
  let slideOutTimeoutId: NodeJS.Timeout | undefined;

  const adjustToCoverWholePage = () => {
    element.classList.add('fixed');
    element.classList.remove('absolute');
  };

  const slideInUp = () => {
    showElement(element);

    slideInTimeoutId = setTimeout(() => {
      element.classList.add('backdrop-blur-sm');
      element.classList.remove('opacity-0');

      dotElement.classList.add('bhr-upload-visual__dot--slide-in-up');

      clearTimeout(slideInTimeoutId);
    });
  };

  const slideOut = (direction: 'up' | 'down') => {
    element.classList.add('delay-100', 'opacity-0');
    element.classList.remove('backdrop-blur-sm');

    const dotclassName = direction === 'up' ? 'bhr-upload-visual__dot--slide-out-up' : 'bhr-upload-visual__dot--slide-out-down';
    dotElement.classList.remove('bhr-upload-visual__dot--slide-in-up');
    dotElement.classList.add(dotclassName);

    slideOutTimeoutId = setTimeout(() => {
      element.classList.remove('delay-100');
      hideElement(element);

      dotElement.classList.remove(dotclassName);

      clearTimeout(slideOutTimeoutId);
    }, TRANSITION_DURATION + 50);
  };

  const slideOutDown = () => {
    slideOut('down');
  };

  const slideOutUp = () => {
    slideOut('up');
  };

  const cleanup = () => {
    clearTimeout(slideInTimeoutId);
    clearTimeout(slideOutTimeoutId);
  };

  return {
    adjustToCoverWholePage,
    cleanup,
    slideInUp,
    slideOutDown,
    slideOutUp,
  };
};
