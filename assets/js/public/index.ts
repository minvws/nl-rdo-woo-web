import '@styles/public/index.scss';

import '@fortawesome/fontawesome-free/css/all.min.css';
import '@minvws/manon/collapsible.js';
import '@minvws/manon/accordion.js';

import { jsEnabled, onBeforeUnload, onDomReady } from '@utils';
import { autoSubmitForm } from './auto-submit-form';
import { searchResults } from './search-results';
import { tabs } from './tabs';

onDomReady(() => {
  jsEnabled();

  const functionalities = [
    searchResults(),
    tabs(),
    autoSubmitForm(),
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
