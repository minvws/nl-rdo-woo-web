import { onOneOfKeysDown } from './one-of-keys-down';

export const onKeyDown = (
  keyName: string | null = null,
  fn: (event: KeyboardEvent) => void = () => {},
  options?: boolean | AddEventListenerOptions,
) => {
  onOneOfKeysDown(keyName ? [keyName] : [], fn, options);
};
