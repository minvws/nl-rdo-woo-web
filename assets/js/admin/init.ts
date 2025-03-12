import { detailsComponents, tabs } from '@js/shared';
import { jsEnabled, onDomReady } from '@utils';
import { clickableRows } from './clickable-row';
import { clickOnSelector } from './click-on-selector';
import { copyToClipboard } from './copy-to-clipboard';
import { toggleDialog } from './dialog';
import { dossierInventoryStatus } from './dossier';
import { manageWidget } from './manage-widget';
import { printPage } from './print';
import { sortTables } from './sort-tables';
import { visibilityToggler } from './visibility-toggler';

export const init = () => {
  onDomReady(() => {
    jsEnabled();

    [
      clickableRows(),
      clickOnSelector(),
      copyToClipboard(),
      detailsComponents(),
      dossierInventoryStatus(),
      manageWidget(),
      printPage(),
      sortTables(),
      tabs(),
      toggleDialog(),
      visibilityToggler(),
    ].forEach((functionality) => {
      functionality.initialize();
    });
  });
};
