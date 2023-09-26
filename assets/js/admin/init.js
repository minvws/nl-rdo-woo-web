import { onDomReady } from '@minvws/manon/utils';
import { beforeUnload } from "../utils/index.js";

import { clickableRows } from './clickable-row.js';
import { detailsComponents } from './details.js';
import { searchPreviews } from './search-previews.js';

onDomReady(() => {
    const functionalities = [
        clickableRows(),
        detailsComponents(),
        searchPreviews(),
    ];

    functionalities.forEach((functionality) => {
        functionality.initialize();
    });

    beforeUnload(() => {
        functionalities.forEach((functionality) => {
            functionality.removeEventListeners();
        });
    });
});
