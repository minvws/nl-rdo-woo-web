import { gridRole } from '../../utils';
import { clickableRows } from '../clickable-row';
import { searchPreviews } from '../utils';

export const dossierSearch = () => {
  const { initialize: initializeClickableRows, cleanup: cleanupClickableRows } = clickableRows();
  const { initialize: initializeGridRole, cleanup: cleanupGridRole } = gridRole();
  const { initialize: initializeSearchPreviews, cleanup: cleanupSearchPreviews } = searchPreviews();

  const initialize = () => {
    initializeSearchPreviews(
      'js-dossier-search-previews',
      addSearchResultsFunctionality,
      removeSearchResultsFunctionality,
    );
  };

  const addSearchResultsFunctionality = (searchResultsElement: HTMLElement) => {
    initializeClickableRows();
    initializeGridRole(searchResultsElement);
  };

  const removeSearchResultsFunctionality = () => {
    cleanupClickableRows();
    cleanupGridRole();
  };

  const cleanup = () => {
    cleanupSearchPreviews();
  };

  return {
    cleanup,
    initialize,
  };
};
