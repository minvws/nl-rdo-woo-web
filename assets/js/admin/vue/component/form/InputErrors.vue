<script setup lang="ts">
import {
  getErrorsId,
  validatorMessages as validatorMessageFunctions,
  type InputValidationErrors,
  type InputValueType,
  type ValidatorMessages,
} from '@admin-fe/form';
import { computed } from 'vue';
import ErrorMessages from './ErrorMessages.vue';

interface Props {
  errors: InputValidationErrors;
  inputId: string;
  validatorMessages?: ValidatorMessages;
  value?: InputValueType;
}

const props = withDefaults(defineProps<Props>(), {
  errors: () => [],
  validatorMessages: () => ({}),
});

const errorMessages = computed(
  () =>
    props.errors
      .map((error) => {
        const validatorMessage =
          props.validatorMessages[error.id] ||
          validatorMessageFunctions[error.id];
        return validatorMessage ? validatorMessage(error, props.value) : null;
      })
      .filter(Boolean) as string[],
);
const id = getErrorsId(props.inputId);
</script>

<template>
  <ErrorMessages :id="id" :messages="errorMessages" />
</template>
