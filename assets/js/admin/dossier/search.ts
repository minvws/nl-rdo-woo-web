import { searchPreviews } from './search-previews';

export const dossierSearch = () => {
  const {
    initialize: initializeSearchPreviews,
    cleanup: cleanupSearchPreviews,
  } = searchPreviews();

  const initialize = () => {
    initializeSearchPreviews('js-dossier-search-previews');
  };

  const cleanup = () => {
    cleanupSearchPreviews();
  };

  return {
    cleanup,
    initialize,
  };
};
