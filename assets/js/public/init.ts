import { detailsComponents } from '@js/shared';
import { jsEnabled, onBeforeUnload, onDomReady } from '@utils';
import { autoSubmitForm } from './auto-submit-form';
import { mainNav } from './main-nav';
import { searchResults } from './search-results';
import { tabs } from './tabs';

export const init = () => {
  onDomReady(() => {
    jsEnabled();

    const functionalities = [
      autoSubmitForm(),
      detailsComponents(),
      mainNav(),
      searchResults(),
      tabs(),
    ];

    functionalities.forEach((functionality) => {
      functionality.initialize();
    });

    onBeforeUnload(() => {
      functionalities.forEach((functionality) => {
        functionality.cleanup();
      });
    });
  });
};
