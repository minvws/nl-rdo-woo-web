import { onOneOfKeysDown } from '../on';

export const gridRole = () => {
  let abortController: AbortController | null = null;
  let rowElements: HTMLElement[] = [];
  let wrapperElement: HTMLElement;

  const enum Selector {
    FocusableElement = '[tabindex="-1"]',
    RowElement = 'tr',
  }

  const MOVE_MULTIPLE_ROWS_BY = 5;
  const NON_EXISTING_INDEX = -100;

  const initialize = (providedWrapperElement: HTMLElement | null) => {
    wrapperElement = providedWrapperElement as HTMLElement;

    if (!wrapperElement) {
      return;
    }

    rowElements = getRowsWithFocusableElements();

    abortController = new AbortController();
    onOneOfKeysDown(['ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'Home', 'End', 'PageDown', 'PageUp'], (event) => {
      const { key } = event;

      switch (key) {
        case 'ArrowDown':
          pressedArrowDown();
          break;
        case 'ArrowLeft':
          pressedArrowLeft();
          break;
        case 'ArrowRight':
          pressedArrowRight();
          break;
        case 'ArrowUp':
          pressedArrowUp();
          break;
        case 'End':
          pressedEnd(event.ctrlKey);
          break;
        case 'Home':
          pressedHome(event.ctrlKey);
          break;
        case 'PageDown':
          pressedPageDown();
          break;
        case 'PageUp':
          pressedPageUp();
          break;
        default:
          break;
      }
    }, { signal: abortController.signal });
  };

  const pressedArrowDown = () => {
    if (isActiveElementOneOfFocusableElements()) {
      goToCell(getCurrentRowIndex() + 1, getCurrentCellIndex());
      return;
    }

    goToCell(0, 0);
  };

  const pressedArrowLeft = () => {
    goToCell(getCurrentRowIndex(), getCurrentCellIndex() - 1);
  };

  const pressedArrowRight = () => {
    goToCell(getCurrentRowIndex(), getCurrentCellIndex() + 1);
  };

  const pressedArrowUp = () => {
    goToCell(getCurrentRowIndex() - 1, getCurrentCellIndex());
  };

  const pressedEnd = (alsoPressedControl: boolean) => {
    const toRowElement = alsoPressedControl ? getLastRowElement() : getCurrentRowElement();
    const toRowIndex = alsoPressedControl ? getLastRowIndex() : getCurrentRowIndex();
    goToCell(toRowIndex, getLastIndexOfRowElement(toRowElement));
  };

  const pressedHome = (alsoPressedControl: boolean) => {
    const toRowIndex = alsoPressedControl ? getFirstRowIndex() : getCurrentRowIndex();
    goToCell(toRowIndex, 0);
  };

  const pressedPageDown = () => {
    const toRowIndex = Math.min(getCurrentRowIndex() + MOVE_MULTIPLE_ROWS_BY, getLastRowIndex());
    goToCell(toRowIndex, getCurrentCellIndex());
  };

  const pressedPageUp = () => {
    const toRowIndex = Math.max(getCurrentRowIndex() - MOVE_MULTIPLE_ROWS_BY, getFirstRowIndex());
    goToCell(toRowIndex, getCurrentCellIndex());
  };

  const goToCell = (rowIndex: number, cellIndex: number) => {
    const rowElement = rowElements[rowIndex];
    if (!rowElement) {
      return;
    }

    const focusableElements = Array.from(rowElement.querySelectorAll<HTMLElement>(Selector.FocusableElement));
    focusableElements[cellIndex]?.focus();
  };

  const getCurrentCellIndex = (): number => {
    if (!isActiveElementOneOfFocusableElements()) {
      return NON_EXISTING_INDEX;
    }

    const currentCellIndex = getFocusableElementsFromRowElement(getCurrentRowElement()).findIndex(
      (focusableElement) => focusableElement === getActiveElement(),
    );

    return currentCellIndex === -1 ? NON_EXISTING_INDEX : currentCellIndex;
  };

  const getCurrentRowIndex = (): number => {
    if (!isActiveElementOneOfFocusableElements()) {
      return NON_EXISTING_INDEX;
    }

    const currentRowElement = getCurrentRowElement();
    const currentRowIndex = rowElements.findIndex(
      (rowElement) => rowElement === currentRowElement,
    );

    return currentRowIndex === -1 ? NON_EXISTING_INDEX : currentRowIndex;
  };

  const getFirstRowIndex = (): number => (isActiveElementOneOfFocusableElements() ? 0 : NON_EXISTING_INDEX);

  const getLastRowIndex = (): number => {
    if (!isActiveElementOneOfFocusableElements()) {
      return NON_EXISTING_INDEX;
    }

    return rowElements.length - 1;
  };

  const getCurrentRowElement = (): HTMLElement | null => getActiveElement()?.closest(Selector.RowElement) || null;
  const getLastRowElement = (): HTMLElement | null => getActiveElement()?.closest(Selector.RowElement) || null;

  const getActiveElement = () => document.activeElement;

  const isActiveElementOneOfFocusableElements = (): boolean => {
    const activeElement = getActiveElement();
    if (!wrapperElement.contains(activeElement)) {
      return false;
    }

    return activeElement?.closest(Selector.FocusableElement) !== null;
  };

  const getRowsWithFocusableElements = () => {
    const allrowElements = wrapperElement.querySelectorAll<HTMLElement>(Selector.RowElement);
    return Array.from(allrowElements).filter((rowElement) => Boolean(rowElement.querySelector<HTMLElement>(Selector.FocusableElement)));
  };

  const getFocusableElementsFromRowElement = (rowElement: HTMLElement | null): HTMLElement[] => (
    rowElement ? Array.from(rowElement?.querySelectorAll<HTMLElement>(Selector.FocusableElement)) : []
  );

  const getLastIndexOfRowElement = (rowElement: HTMLElement | null): number => {
    const focusableElements = getFocusableElementsFromRowElement(rowElement);
    return focusableElements.length === 0 ? NON_EXISTING_INDEX : focusableElements.length - 1;
  };

  const cleanup = () => {
    if (!abortController) {
      return;
    }

    abortController.abort();
    abortController = null;
  };

  return {
    cleanup,
    initialize,
  };
};
