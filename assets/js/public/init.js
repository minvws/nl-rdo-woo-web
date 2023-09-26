import { onDomReady } from '@minvws/manon/utils.js';

onDomReady(function () {
    document.documentElement.classList.remove('no-js');
    document.documentElement.classList.add('js');
});