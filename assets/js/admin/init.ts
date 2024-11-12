import { detailsComponents } from '@js/shared';
import { jsEnabled, onBeforeUnload, onDomReady } from '@utils';
import { clickableRows } from './clickable-row';
import { clickOnSelector } from './click-on-selector';
import { copyToClipboard } from './copy-to-clipboard';
import { toggleDialog } from './dialog';
import {
  dossierDocumentsStatus,
  dossierInventoryStatus,
  dossierLinkSearch,
  dossierSearch,
} from './dossier';
import { manageWidget } from './manage-widget';
import { printPage } from './print';
import { sortTables } from './sort-tables';
import { uploadAreas } from './upload-areas';
import { visibilityToggler } from './visibility-toggler';

export const init = () => {
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
      manageWidget(),
      printPage(),
      sortTables(),
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
};
