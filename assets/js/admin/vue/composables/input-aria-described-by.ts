import { getErrorsId, getHelpId } from '../form';

export const useInputAriaDescribedBy = (
  inputId: string,
  helpText: string,
  hasErrors: boolean,
) => {
  const ids = [];
  if (hasErrors) {
    ids.push(getErrorsId(inputId));
  }
  if (helpText) {
    ids.push(getHelpId(inputId));
  }
  return ids.join(' ');
};
