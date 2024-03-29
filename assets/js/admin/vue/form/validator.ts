import { InputErrorId, type InputValueType, type Validator } from './interface';

export const required = (): Validator => (value: InputValueType) => {
  if (!value) {
    return { id: InputErrorId.Required };
  }

  return undefined;
};

export const email = (): Validator => (value: InputValueType) => {
  const error = { id: InputErrorId.Email };
  if (typeof value !== 'string') {
    return error;
  }

  // eslint-disable-next-line max-len
  const regExp = /^(?=.{1,254}$)(?=.{1,64}@)[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+)*@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
  if (!regExp.test(value)) {
    return error;
  }

  return undefined;
};

export const minLength = (min: number): Validator => (value: InputValueType) => {
  if (typeof value !== 'string') {
    return undefined;
  }

  if (value.length >= min) {
    return undefined;
  }

  return {
    actualLength: value.length,
    id: InputErrorId.MinLength,
    minLength: min,
    tooLittleLength: min - value.length,
  };
};
