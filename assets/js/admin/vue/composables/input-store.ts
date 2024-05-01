import { ref, Ref, watch } from 'vue';
import type { InputValueType, InputValidationErrors, Validator } from '../form/interface';

export interface InputStore {
  addSubmitValidationError: (error: string) => void;
  errors: InputValidationErrors;
  hasVisibleErrors: boolean;
  isDirty: boolean;
  isInvalid: boolean;
  isPristine: boolean;
  isTouched: boolean;
  isValid: boolean;
  label: string;
  markAsShouldDisplayErrors: () => void;
  markAsTouched: () => void;
  name: string;
  reset: () => void;
  resetSubmitValidationErrors: () => void;
  setValidators: (newValidators: Validator[]) => void;
  setValue: (value: InputValueType) => void;
  submitValidationErrors: string[];
  value: InputValueType;
}

const getStaticErrors = (inputValue: InputValueType, validators: Validator[] = []): InputValidationErrors => {
  const errorCollection = validators.reduce((accumulated, validator) => {
    const error = validator(inputValue);
    if (error) {
      accumulated.set(error.id, error);
    }
    return accumulated;
  }, new Map());

  return Array.from(errorCollection.values());
};

export const useInputStore = (name: string, label: string, value: Ref<InputValueType>, initialValidators: Validator[] = []): InputStore => {
  const validators = ref(initialValidators);
  const staticErrors = ref<InputValidationErrors>(getStaticErrors(value.value, validators.value));
  const submitValidationErrors = ref<string[]>([]);
  const isDirty = ref(false);
  const isTouched = ref(false);
  const shouldDisplayErrors = ref(false);

  const updateStaticErrors = () => {
    staticErrors.value = getStaticErrors(value.value, validators.value);
  };

  const setValidators = (newValidators: Validator[]) => {
    validators.value = newValidators;
    updateStaticErrors();
  };

  const setValue = (newValue: InputValueType) => {
    // eslint-disable-next-line no-param-reassign
    value.value = newValue;
  };

  watch(value, () => {
    isDirty.value = true;
    updateStaticErrors();
  });

  return {
    get errors() {
      return staticErrors.value;
    },
    get hasVisibleErrors() {
      if (submitValidationErrors.value.length > 0) {
        return true;
      }

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
      return staticErrors.value.length > 0;
    },
    get isPristine() {
      return !this.isDirty;
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
    get submitValidationErrors() {
      return submitValidationErrors.value;
    },
    get value() {
      return value.value;
    },
    addSubmitValidationError(error: string) {
      submitValidationErrors.value.push(error);
    },
    resetSubmitValidationErrors() {
      submitValidationErrors.value = [];
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
    setValidators,
    setValue,
  };
};
