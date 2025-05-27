import { endOfDay, isAfter } from 'date-fns';
import { InputErrorId, type InputValueType, type Validator } from './interface';

export const required = (): Validator => (value: InputValueType) => {
  const error = { id: InputErrorId.Required };
  if (value === 0) {
    return undefined;
  }

  if (!value) {
    return error;
  }

  if (typeof value === 'string' && value.trim() === '') {
    return error;
  }

  if (Array.isArray(value) && value.length === 0) {
    return error;
  }

  if (typeof value === 'object' && Object.keys(value).length === 0) {
    return error;
  }

  return undefined;
};

export const email = (): Validator => (value: InputValueType) => {
  const error = { id: InputErrorId.Email };
  if (typeof value !== 'string') {
    return error;
  }

  const regExp =
    /^(?=.{1,254}$)(?=.{1,64}@)[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+)*@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
  if (!regExp.test(value)) {
    return error;
  }

  return undefined;
};

export const forbidden =
  (forbiddenValues: string[]): Validator =>
  (value: InputValueType) => {
    if (!value) {
      return undefined;
    }

    if (
      forbiddenValues.some(
        (forbiddenValue) =>
          forbiddenValue.toLowerCase() === (value as string).toLowerCase(),
      )
    ) {
      return { id: InputErrorId.Forbidden };
    }

    return undefined;
  };

export const minLength =
  (min: number): Validator =>
  (value: InputValueType) => {
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

export const dateMaxUntilToday = (): Validator => {
  const today = endOfDay(new Date());
  return (value: InputValueType) => {
    if (typeof value !== 'string') {
      return undefined;
    }

    if (isAfter(new Date(value), today)) {
      return { id: InputErrorId.DateMaxUntilToday };
    }

    return undefined;
  };
};
