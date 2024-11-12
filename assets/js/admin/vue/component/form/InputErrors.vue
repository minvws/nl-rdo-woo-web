<script setup>
import {
  getErrorsId,
  validatorMessages as validatorMessageFunctions,
} from '@admin-fe/form';
import { computed } from 'vue';
import ErrorMessages from './ErrorMessages.vue';

const props = defineProps({
  errors: {
    type: Array,
    default: () => [],
    required: true,
  },
  inputId: {
    type: String,
    required: true,
  },
  validatorMessages: {
    type: Object,
    default: () => ({}),
  },
  value: {
    type: [Array, Boolean, Number, Object, String],
  },
});

const errorMessages = computed(() =>
  props.errors
    .map((error) => {
      const validatorMessage =
        props.validatorMessages[error.id] ||
        validatorMessageFunctions[error.id];
      return validatorMessage ? validatorMessage(error, props.value) : null;
    })
    .filter(Boolean),
);
const id = getErrorsId(props.inputId);
</script>

<template>
  <ErrorMessages :id="id" :messages="errorMessages" />
</template>
