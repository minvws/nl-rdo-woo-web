export const jsEnabled = (): void => {
  document.documentElement.classList.remove('no-js');
  document.documentElement.classList.add('js');
};
