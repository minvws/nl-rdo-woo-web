import { onOneOfKeysDown } from '../on';
import { uniqueId } from '../id';
import { hideElement } from '../element';

interface Settings {
  defaultOptionClassNames: string[];
  highlightedOptionClassNames: string[];
}

export const listboxRole = (settings: Partial<Settings> = {}) => {
  let activatedOptionByPointerId: string | null = null;
  let abortController: AbortController | null = null;
  let controlledByElement: HTMLInputElement;
  let currentOptionIndex: number;
  let onSelect: (optionElement: HTMLElement) => void;
  let optionElements: HTMLElement[];
  let wrapperElement: HTMLElement;

  const UNSELECTED_OPTION_INDEX = -1;

  const enum Selector {
    Option = '[role="option"]',
    Selected = '[aria-selected="true"]',
  }

  const initialize = (
    providedWrapperElement: HTMLElement,
    providedControlledByElement: HTMLInputElement,
    onSelectFn: (optionElement: HTMLElement) => void,
  ) => {
    controlledByElement = providedControlledByElement;
    onSelect = onSelectFn;
    wrapperElement = providedWrapperElement;

    if (!controlledByElement || !wrapperElement) {
      return;
    }

    resetActivatedOptionByPointerId();
    resetCurrentOptionIndex();

    optionElements = Array.from(
      wrapperElement.querySelectorAll(Selector.Option),
    );

    abortController = new AbortController();
    addNavigationFunctionality();
    addOptionsFunctionality();
  };

  const addNavigationFunctionality = () => {
    const oneOfKeys = [
      'ArrowDown',
      'ArrowUp',
      'End',
      'Enter',
      'Home',
      'PageDown',
      'PageUp',
    ];
    onOneOfKeysDown(
      oneOfKeys,
      (event) => {
        const { key } = event;

        switch (key) {
          case 'ArrowDown':
            pressedArrowDown();
            break;
          case 'ArrowUp':
            pressedArrowUp();
            break;
          case 'Enter':
            pressedEnter(event);
            break;
          default:
            break;
        }
      },
      { signal: abortController?.signal },
    );
  };

  const addOptionsFunctionality = () => {
    optionElements.forEach((optionElement) => {
      let optionId = optionElement.getAttribute('id');
      if (!optionId) {
        optionId = uniqueId('option');
        optionElement.setAttribute('id', optionId);
      }
      optionElement.classList.add(...getSettings().defaultOptionClassNames);

      optionElement.addEventListener(
        'click',
        () => {
          selectOption(optionElement);
        },
        { signal: abortController?.signal },
      );

      optionElement.addEventListener(
        'pointerover',
        () => {
          activatedOptionByPointerId = optionId;
          highlightOption(optionElement);
        },
        { signal: abortController?.signal },
      );

      optionElement.addEventListener(
        'pointerout',
        () => {
          resetActivatedOptionByPointerId();
          unhighlightOption(optionElement);
        },
        { signal: abortController?.signal },
      );
    });
  };

  const highlightOption = (optionElement: HTMLElement | null) => {
    optionElement?.classList.add(...getSettings().highlightedOptionClassNames);
  };

  const unhighlightOption = (optionElement: HTMLElement | null) => {
    if (activatedOptionByPointerId === optionElement?.id) {
      return;
    }

    optionElement?.classList.remove(
      ...getSettings().highlightedOptionClassNames,
    );
  };

  const activateOptionByIndex = (index: number) => {
    activateOption(optionElements[index]);
    currentOptionIndex = index;
  };

  const activateOption = (optionElement: HTMLElement | null) => {
    deactivateOptionByIndex(currentOptionIndex);

    highlightOption(optionElement);
    optionElement?.setAttribute('aria-selected', 'true');
    controlledByElement.setAttribute(
      'aria-activedescendant',
      optionElement?.id as string,
    );
  };

  const deactivateOptionByIndex = (index: number) => {
    deactivateOption(optionElements[index]);
  };

  const deactivateOption = (optionElement: HTMLElement | null) => {
    unhighlightOption(optionElement);
    optionElement?.removeAttribute('aria-selected');
    controlledByElement.removeAttribute('aria-activedescendant');
  };

  const selectOptionByIndex = (index: number) => {
    selectOption(optionElements[index]);
  };

  const selectOption = (optionElement: HTMLElement | null) => {
    if (!optionElement) {
      return;
    }

    controlledByElement.value = optionElement.textContent?.trim() || '';
    hideElement(wrapperElement);
    onSelect(optionElement);
  };

  const pressedArrowDown = () => {
    activateOptionByIndex(getNextOptionIndex());
  };

  const pressedArrowUp = () => {
    activateOptionByIndex(getPreviousOptionIndex());
  };

  const pressedEnter = (event: KeyboardEvent) => {
    event.preventDefault();

    if (currentOptionIndex === UNSELECTED_OPTION_INDEX) {
      return;
    }

    selectOptionByIndex(currentOptionIndex);
  };

  const getNextOptionIndex = () => {
    const nextOptionIndex = currentOptionIndex + 1;
    return nextOptionIndex > getLastOptionIndex() ? 0 : nextOptionIndex;
  };

  const getPreviousOptionIndex = () => {
    const previousOptionIndex = currentOptionIndex - 1;
    return previousOptionIndex < 0 ? getLastOptionIndex() : previousOptionIndex;
  };

  const getLastOptionIndex = () => optionElements.length - 1;

  const getSettings = (): Settings => ({
    defaultOptionClassNames: settings.defaultOptionClassNames || [],
    highlightedOptionClassNames: settings.highlightedOptionClassNames || [],
  });

  const resetActivatedOptionByPointerId = () => {
    activatedOptionByPointerId = null;
  };

  const resetCurrentOptionIndex = () => {
    currentOptionIndex = UNSELECTED_OPTION_INDEX;
  };

  const cleanup = () => {
    abortController?.abort();
    abortController = null;
  };

  return {
    cleanup,
    initialize,
  };
};
