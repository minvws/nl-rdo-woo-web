import { clickableRows } from '../clickable-row';
import { searchPreviews } from '../utils';

export const dossierSearch = () => {
  const { initialize: initializeClickableRows, cleanup: cleanupClickableRows } = clickableRows();
  const { initialize: initializeSearchPreviews, cleanup: cleanupSearchPreviews } = searchPreviews();

  const initialize = () => {
    initializeSearchPreviews(
      'js-dossier-search-previews',
      initializeClickableRows,
      cleanupClickableRows,
    );
  };

  const cleanup = () => {
    cleanupSearchPreviews();
  };

  return {
    cleanup,
    initialize,
  };
};
