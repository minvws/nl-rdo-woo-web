import { z } from 'zod';
import type { FormValue } from '../form/interface';
import type { InputStore } from './input-store';
import type { MultiInputStore } from './multi-input-store';

export interface FormStore {
  addInput: (inputStore: InputStore | MultiInputStore) => void;
  addSubmitValidationError: (error: string, path?: string) => void;
  deleteInput: (name: string) => void;
  getDirtyValue: () => FormValue;
  getInputStore: (name: string) => InputStore | MultiInputStore | undefined;
  getValue: () => FormValue;
  isDirty: () => boolean;
  isPristine: () => boolean;
  isInvalid: () => boolean;
  isValid: () => boolean;
  markAsShouldDisplayErrors: () => void;
  reset: () => void;
  resetSubmitValidationErrors: () => void;
  submit: (value: FormValue, dirtyValue: FormValue) => Promise<Response>;
  submitResponseSchema: z.ZodSchema;
}

export const useFormStore = (
  onSubmitFunction: (
    value: FormValue,
    dirtyValue: FormValue,
  ) => Promise<Response>,
  submitResponseSchema: z.ZodSchema,
): FormStore => {
  const inputStores: Map<string, InputStore | MultiInputStore> = new Map();

  const addInput = (inputStore: InputStore | MultiInputStore) => {
    inputStores.set(inputStore.name, inputStore);
  };

  const getInputStore = (name: string) => inputStores.get(name);
  const getInputStores = () => [...inputStores.values()];

  const deleteInput = (name: string) => {
    inputStores.delete(name);
  };

  const getValue = (): FormValue =>
    getInputStores().reduce(
      (accumulated, inputStore) => ({
        ...accumulated,
        [inputStore.name]: inputStore.value,
      }),
      {},
    );

  const getDirtyValue = (): FormValue =>
    getInputStores().reduce((accumulated, inputStore) => {
      if (inputStore.isDirty) {
        return { ...accumulated, [inputStore.name]: inputStore.value };
      }

      return accumulated;
    }, {});

  const addSubmitValidationError = (error: string, path?: string) => {
    const foundInputStore = path ? getInputStore(path) : undefined;
    foundInputStore?.addSubmitValidationError(error, path);
  };

  const resetSubmitValidationErrors = () => {
    getInputStores().forEach((inputStore) =>
      inputStore.resetSubmitValidationErrors(),
    );
  };

  const isDirty = () =>
    getInputStores().some((inputStore) => inputStore.isDirty);
  const isPristine = () => !isDirty();
  const isInvalid = () =>
    getInputStores().some((inputStore) => inputStore.isInvalid);
  const isValid = () => !isInvalid();
  const markAsShouldDisplayErrors = () =>
    getInputStores().forEach((inputStore) =>
      inputStore.markAsShouldDisplayErrors(),
    );
  const reset = () =>
    getInputStores().forEach((inputStore) => inputStore.reset());

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
    submitResponseSchema,
  };
};
