export const onOneOfKeysDown = (
  keyNames: string[],
  fn: (event: KeyboardEvent) => void,
  options?: boolean | AddEventListenerOptions,
) => {
  window.addEventListener(
    'keydown',
    (event) => {
      if (keyNames.length > 0 && !keyNames.includes(event.key)) {
        return;
      }

      fn(event);
    },
    options,
  );
};
