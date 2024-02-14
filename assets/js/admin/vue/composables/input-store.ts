import { ref, Ref, watch } from 'vue';
import type { InputValueType, InputValidationErrors, Validator } from '../form/interface';

export interface InputStore {
  errors: InputValidationErrors;
  hasVisibleErrors: boolean;
  isDirty: boolean;
  isInvalid: boolean;
  isTouched: boolean;
  isValid: boolean;
  label: string;
  markAsShouldDisplayErrors: () => void;
  markAsTouched: () => void;
  name: string;
  reset: () => void;
  value: InputValueType;
}

const getErrors = (inputValue: InputValueType, validators: Validator[] = []): InputValidationErrors => {
  const errorCollection = validators.reduce((accumulated, validator) => {
    const error = validator(inputValue);
    if (error) {
      accumulated.set(error.id, error);
    }
    return accumulated;
  }, new Map());

  return Array.from(errorCollection.values());
};

export const useInputStore = (name: string, label: string, value: Ref<InputValueType>, validators: Validator[] = []): InputStore => {
  const errors = ref<InputValidationErrors>(getErrors(value.value, validators));
  const isDirty = ref(false);
  const isTouched = ref(false);
  const shouldDisplayErrors = ref(false);

  watch(value, () => {
    isDirty.value = true;
    errors.value = getErrors(value.value, validators);
  });

  return {
    get errors() {
      return errors.value;
    },
    get hasVisibleErrors() {
      if (this.isValid) {
        return false;
      }

      if (shouldDisplayErrors.value) {
        return true;
      }

      return this.isDirty && this.isTouched;
    },
    get isDirty() {
      return isDirty.value;
    },
    get isInvalid() {
      return errors.value.length > 0;
    },
    get isTouched() {
      return isTouched.value;
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
    get value() {
      return value.value;
    },
    markAsShouldDisplayErrors() {
      shouldDisplayErrors.value = true;
    },
    markAsTouched() {
      isTouched.value = true;
    },
    reset() {
      isDirty.value = false;
      isTouched.value = false;
      shouldDisplayErrors.value = false;
    },
  };
};
