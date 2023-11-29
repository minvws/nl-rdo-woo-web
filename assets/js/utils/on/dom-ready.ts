import { getDocument } from '../browser';

export const onDomReady = (fn: () => void) => {
  if (getDocument().readyState !== 'loading') {
    fn();
    return;
  }

  getDocument().addEventListener('DOMContentLoaded', fn);
};
