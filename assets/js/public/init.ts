import { detailsComponents, tabs } from '@js/shared';
import { jsEnabled, onDomReady } from '@utils';
import { autoSubmitForm } from './auto-submit-form';
import { collapsibles } from './collapsibles';
import { mainNav } from './main-nav';
import { searchResults } from './search-results';

export const init = () => {
  onDomReady(() => {
    jsEnabled();

    [
      autoSubmitForm(),
      collapsibles(),
      detailsComponents(),
      mainNav(),
      searchResults(),
      tabs(),
    ].forEach((functionality) => {
      functionality.initialize();
    });
  });
};
