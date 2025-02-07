import { getLocation } from '../utils';

export const clickableRows = () => {
  let abortController: AbortController;

  const initialize = () => {
    cleanup();

    abortController = new AbortController();
    document
      .querySelectorAll<HTMLAnchorElement>('.js-bhr-clickable-row-link')
      .forEach((clickableRowLink) => {
        const tableRow = clickableRowLink.closest('tr') as HTMLTableRowElement;

        if (!tableRow) {
          return;
        }

        tableRow.classList.add('bhr-clickable-row');

        tableRow.addEventListener(
          'click',
          () => {
            getLocation().assign(clickableRowLink.href);
          },
          { signal: abortController.signal },
        );
      });
  };

  const cleanup = () => {
    abortController?.abort();
  };

  return {
    cleanup,
    initialize,
  };
};
