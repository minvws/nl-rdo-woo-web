<script setup lang="ts">
import {
  type FormStore,
  useInputAriaDescribedBy,
  useInputStore,
} from '@admin-fe/composables';
import type { Validator } from '@admin-fe/form/interface';
import { uniqueId } from '@js/utils';
import { computed, inject, ref, watch } from 'vue';
import ErrorMessages from './ErrorMessages.vue';
import FormHelp from './FormHelp.vue';
import FormLabel from './FormLabel.vue';
import InputErrors from './InputErrors.vue';

interface Props {
  class?: string;
  hasFormRow?: boolean;
  helpText?: string;
  label?: string;
  name: string;
  required?: boolean;
  type?: string;
  validators?: Validator[];
  value?: string;
}

const props = withDefaults(defineProps<Props>(), {
  class: '',
  hasFormRow: true,
  helpText: '',
  label: '',
  required: true,
  type: 'text',
  validators: () => [],
  value: '',
});

const inputId = uniqueId('input');
const value = ref(props.value);

watch(
  () => props.value,
  (newValue) => {
    value.value = newValue;
  },
);

const inputStore = useInputStore(
  props.name,
  props.label,
  value,
  props.validators,
);
const inputClass = computed(() => ({
  'bhr-input-text': true,
  'bhr-input-text--invalid': inputStore.hasVisibleErrors,
  [props.class]: true,
}));
const formRowClass = computed(() => ({
  'bhr-form-row': props.hasFormRow,
  'bhr-form-row--invalid': props.hasFormRow && inputStore.hasVisibleErrors,
}));
const ariaDescribedBy = computed(() =>
  useInputAriaDescribedBy(inputId, props.helpText, inputStore.hasVisibleErrors),
);

(inject('form') as FormStore).addInput(inputStore);
</script>

<template>
  <div :class="formRowClass">
    <FormLabel v-if="props.label" :for="inputId" :required="props.required">
      {{ props.label }}
    </FormLabel>

    <FormHelp :inputId="inputId" v-if="props.helpText">{{
      props.helpText
    }}</FormHelp>

    <div aria-live="assertive">
      <InputErrors
        :errors="inputStore.errors"
        :inputId="inputId"
        :value="value"
        v-if="inputStore.hasVisibleErrors"
      />

      <ErrorMessages
        :messages="inputStore.submitValidationErrors"
        v-if="inputStore.hasVisibleErrors"
      />
    </div>

    <input
      @blur="inputStore.markAsTouched"
      :aria-describedby="ariaDescribedBy"
      :aria-invalid="inputStore.hasVisibleErrors"
      :class="inputClass"
      :id="inputId"
      :name="props.name"
      :type="props.type"
      :required="props.required"
      v-model="value"
    />
  </div>
</template>
