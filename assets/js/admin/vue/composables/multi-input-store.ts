import { Ref, ref } from 'vue';
import type { InputValidationErrors, InputValueType } from '../form/interface';
import { InputStore } from './input-store';

export interface MultiInputStore
  extends Omit<
    InputStore,
    'addSubmitValidationError' | 'submitValidationErrors'
  > {
  addInputStore: (inputStore: InputStore) => void;
  addSubmitValidationError: (error: string, path?: string) => void;
  findInputStore: (path: string) => InputStore | undefined;
  makeDirty: () => void;
  removeInputStore: (inputStore: InputStore) => void;
  submitValidationErrors: Record<string, string[]>;
}

export const useMultiInputStore = (
  name: string,
  label: string,
  value: Ref<InputValueType>,
): MultiInputStore => {
  const inputStores = new Map<string, InputStore>();

  const isDirty = ref(false);

  const addInputStore = (inputStore: InputStore) => {
    inputStores.set(inputStore.name, inputStore);
  };

  const findInputStore = (inputStoreName: string) =>
    inputStores.get(inputStoreName);

  const removeInputStore = (inputStore: InputStore) => {
    inputStores.delete(inputStore.name);
  };

  const getInputStores = () => Array.from(inputStores.values());

  return {
    addInputStore,
    removeInputStore,
    get hasVisibleErrors() {
      return getInputStores().some((inputStore) => inputStore.hasVisibleErrors);
    },
    get errors() {
      return getInputStores().reduce((accumulatedErrors, inputStore) => {
        if (inputStore.isValid) {
          return accumulatedErrors;
        }

        accumulatedErrors.push(...inputStore.errors);
        return accumulatedErrors;
      }, [] as InputValidationErrors);
    },
    get isDirty() {
      if (isDirty.value === true) {
        return true;
      }
      return getInputStores().some((inputStore) => inputStore.isDirty);
    },
    get isInvalid() {
      return getInputStores().some((inputStore) => inputStore.isInvalid);
    },
    get isPristine() {
      return !this.isDirty;
    },
    get isTouched() {
      return getInputStores().some((inputStore) => inputStore.isTouched);
    },
    get isValid() {
      return !this.isInvalid;
    },
    get label() {
      return label;
    },
    get name() {
      return name;
    },
    get submitValidationErrors() {
      return getInputStores().reduce(
        (accumulated, inputStore) => ({
          ...accumulated,
          [inputStore.name]: inputStore.submitValidationErrors,
        }),
        {} as Record<string, string[]>,
      );
    },
    get value() {
      return value.value;
    },
    addSubmitValidationError(error: string, path?: string) {
      const foundInputStore = path ? findInputStore(path) : undefined;
      foundInputStore?.addSubmitValidationError(error, path);
    },
    findInputStore,
    makeDirty() {
      isDirty.value = true;
    },
    resetSubmitValidationErrors() {
      getInputStores().forEach((inputStore) =>
        inputStore.resetSubmitValidationErrors(),
      );
    },
    markAsShouldDisplayErrors() {
      getInputStores().forEach((inputStore) =>
        inputStore.markAsShouldDisplayErrors(),
      );
    },
    markAsTouched() {
      getInputStores().forEach((inputStore) => inputStore.markAsTouched());
    },
    reset() {
      isDirty.value = false;
      getInputStores().forEach((inputStore) => inputStore.reset());
    },
    setValidators: () => {},
    setValue: () => {},
  };
};
