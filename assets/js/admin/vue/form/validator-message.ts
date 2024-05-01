import { pluralize } from '@js/utils';
import type { InputValidationError, ValidatorMessage } from './interface';

export const required: ValidatorMessage = () => 'Deze waarde mag niet leeg zijn.';

export const email: ValidatorMessage = () => 'Vul een geldig e-mailadres in (zoals voor@beeld.com)';

export const forbidden: ValidatorMessage = () => 'Deze waarde is niet toegestaan';

export const dateMaxUntilToday: ValidatorMessage = () => 'Deze datum mag niet in de toekomst liggen';

export const minLength: ValidatorMessage = (error) => {
  const { minLength: minLengthValue, tooLittleLength } = error as InputValidationError & {
    actualLength: number;
    minLength: number;
    tooLittleLength: number;
  };

  return `Vul je invoer aan met ${tooLittleLength} ${pluralize(
    'karakter',
    'karakters',
    tooLittleLength,
  )}. Het minimum aantal karakters is ${minLengthValue}.`;
};
