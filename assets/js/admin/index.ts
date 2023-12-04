import '@styles/admin/index.css';

import { jsEnabled, onBeforeUnload, onDomReady } from '@utils';
import { clickableRows } from './clickable-row';
import { clickOnSelector } from './click-on-selector';
import { copyToClipboard } from './copy-to-clipboard';
import { detailsComponents } from './details';
import { toggleDialog } from './dialog';
import { dossierDocumentsStatus, dossierInventoryStatus, dossierLinkSearch, dossierSearch } from './dossier';
import { printPage } from './print';
import { uploadAreas } from './upload-areas';
import { visibilityToggler } from './visibility-toggler';

onDomReady(() => {
  jsEnabled();

  const functionalities = [
    clickableRows(),
    clickOnSelector(),
    copyToClipboard(),
    detailsComponents(),
    dossierDocumentsStatus(),
    dossierInventoryStatus(),
    dossierLinkSearch(),
    dossierSearch(),
    printPage(),
    toggleDialog(),
    uploadAreas(),
    visibilityToggler(),
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
