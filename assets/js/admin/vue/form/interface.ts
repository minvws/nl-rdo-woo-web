export type FormValue = Record<string, InputValueType>;
export type InputValueType =
  | boolean
  | number
  | string
  | object
  | Array<string>
  | undefined;

export const enum InputErrorId {
  DateMaxUntilToday = 'dateMaxUntilToday',
  Email = 'email',
  Forbidden = 'forbidden',
  MinLength = 'minLength',
  Required = 'required',
}

export type InputValidationError = { id: string } & Record<string, unknown>;
export type InputValidationErrors = InputValidationError[];
export type Validator = (
  value: InputValueType,
) => InputValidationError | undefined;
export type ValidatorMessage = (
  error?: InputValidationError,
  value?: InputValueType,
) => string;
export type ValidatorMessages = Record<string, ValidatorMessage>;

export interface SelectOption {
  label: string;
  value: string;
}

export type SelectOptions = SelectOption[];

export interface Optgroup {
  label: string;
  options: SelectOptions;
}
