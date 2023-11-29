import { getLocation } from '../utils/browser';
import { onFocusIn, onFocusOut } from '../utils/on';

const enum ClickableRowClassNames {
  Clickable = 'bhr-clickable-row',
  WithColor = 'bhr-clickable-row--with-color',
}

export const clickableRows = () => {
  let abortController: AbortController;

  const initialize = () => {
    cleanup();

    const clickableRowFocusableElements: NodeListOf<HTMLElement> = document.querySelectorAll('tr .js-clickable-row__focusable');
    if (!clickableRowFocusableElements.length) {
      return;
    }

    abortController = new AbortController();
    clickableRowFocusableElements.forEach((clickableRowFocusableElement) => {
      const tableRow = clickableRowFocusableElement.closest('tr') as HTMLTableRowElement;

      tableRow.classList.remove(ClickableRowClassNames.WithColor);
      tableRow.classList.add(ClickableRowClassNames.Clickable);

      onFocusOut(tableRow, () => {
        tableRow.classList.remove(ClickableRowClassNames.WithColor);
      }, { signal: abortController?.signal });

      onFocusIn(tableRow, () => {
        tableRow.classList.add(ClickableRowClassNames.WithColor);
      }, { signal: abortController?.signal });

      if (clickableRowFocusableElement.tagName === 'A') {
        tableRow.addEventListener('click', () => {
          getLocation().assign((clickableRowFocusableElement as HTMLAnchorElement).href);
        }, { signal: abortController?.signal });
      }
    });
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
