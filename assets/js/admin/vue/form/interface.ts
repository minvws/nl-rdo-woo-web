export type FormValue = Record<string, InputValueType>;
export type InputValueType = boolean | number | string;

export const enum InputErrorId {
  Email = 'email',
  MinLength = 'minLength',
  Required = 'required',
}

export type InputValidationError = { id: string } & Record<string, unknown>;
export type InputValidationErrors = InputValidationError[];
export type Validator = (value: InputValueType) => InputValidationError | undefined;
export type ValidatorMessage = (error?: InputValidationError, value?: InputValueType) => string;
