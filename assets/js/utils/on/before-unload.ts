import { getWindow } from '../browser';

export const onBeforeUnload = (fn: () => void) => {
  getWindow().addEventListener('beforeunload', fn, { once: true });
};
