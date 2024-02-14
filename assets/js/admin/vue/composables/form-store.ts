import type { InputStore } from './input-store';
import type { FormValue } from '../form/interface';

export const useFormStore = (onSubmitFunction: (value: FormValue) => Promise<void>) => {
  const inputStores: InputStore[] = [];

  const addInput = (inputStore: InputStore) => {
    inputStores.push(inputStore);
  };

  const getValue = (): FormValue => inputStores.reduce((accumulated, inputStore) => ({
    ...accumulated, [inputStore.name]: inputStore.value,
  }), {});

  const isDirty = () => inputStores.some((inputStore) => inputStore.isDirty);
  const isPristine = () => !isDirty();
  const isInvalid = () => inputStores.some((inputStore) => inputStore.isInvalid);
  const isValid = () => !isInvalid();
  const markAsShouldDisplayErrors = () => inputStores.forEach((inputStore) => inputStore.markAsShouldDisplayErrors());
  const reset = () => inputStores.forEach((inputStore) => inputStore.reset());

  return {
    addInput,
    getValue,
    isDirty,
    isPristine,
    isInvalid,
    isValid,
    markAsShouldDisplayErrors,
    reset,
    submit: onSubmitFunction,
  };
};
