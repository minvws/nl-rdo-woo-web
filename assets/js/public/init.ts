import { detailsComponents, tabs } from '@js/shared';
import { jsEnabled, onDomReady } from '@utils';
import { autoSubmitForm } from './auto-submit-form';
import { collapsibles } from './collapsibles';
import { mainNav } from './main-nav';
import { searchResults } from './search-results';
import { searchSpreadsheetFile } from './search-spreadsheet-file';
import { sortableTable } from './sortable-table';

export const init = () => {
  onDomReady(() => {
    jsEnabled();

    [
      autoSubmitForm(),
      collapsibles(),
      detailsComponents(),
      mainNav(),
      searchResults(),
      searchSpreadsheetFile(),
      sortableTable(),
      tabs(),
    ].forEach((functionality) => {
      functionality.initialize();
    });
  });
};
