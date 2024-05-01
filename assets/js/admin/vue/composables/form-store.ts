import type { InputStore } from './input-store';
import type { FormValue } from '../form/interface';

export const useFormStore = (onSubmitFunction: (value: FormValue) => Promise<void>) => {
  const inputStores: Map<string, InputStore> = new Map();

  const addInput = (inputStore: InputStore) => {
    inputStores.set(inputStore.name, inputStore);
  };

  const getInputStore = (name: string) => inputStores.get(name);
  const getInputStores = () => [...inputStores.values()];

  const deleteInput = (name: string) => {
    inputStores.delete(name);
  };

  const getValue = (): FormValue => getInputStores().reduce((accumulated, inputStore) => ({
    ...accumulated, [inputStore.name]: inputStore.value,
  }), {});

  const getDirtyValue = (): FormValue => getInputStores().reduce((accumulated, inputStore) => {
    if (inputStore.isDirty) {
      return { ...accumulated, [inputStore.name]: inputStore.value };
    }

    return accumulated;
  }, {});

  const addSubmitValidationError = (path: string, error: string) => {
    const foundInputStore = getInputStore(path);
    if (foundInputStore) {
      foundInputStore.addSubmitValidationError(error);
    }
  };

  const resetSubmitValidationErrors = () => {
    getInputStores().forEach((inputStore) => inputStore.resetSubmitValidationErrors());
  };

  const isDirty = () => getInputStores().some((inputStore) => inputStore.isDirty);
  const isPristine = () => !isDirty();
  const isInvalid = () => getInputStores().some((inputStore) => inputStore.isInvalid);
  const isValid = () => !isInvalid();
  const markAsShouldDisplayErrors = () => getInputStores().forEach((inputStore) => inputStore.markAsShouldDisplayErrors());
  const reset = () => getInputStores().forEach((inputStore) => inputStore.reset());

  return {
    addInput,
    addSubmitValidationError,
    deleteInput,
    getDirtyValue,
    getInputStore,
    getValue,
    isDirty,
    isPristine,
    isInvalid,
    isValid,
    markAsShouldDisplayErrors,
    reset,
    resetSubmitValidationErrors,
    submit: onSubmitFunction,
  };
};
