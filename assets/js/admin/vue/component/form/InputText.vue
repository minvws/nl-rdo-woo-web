<script setup>
  import { useInputAriaDescribedBy, useInputStore } from '@admin-fe/composables';
  import { uniqueId } from '@js/utils';
  import { computed, inject, ref, watch } from 'vue';
  import FormHelp from './FormHelp.vue';
  import FormLabel from './FormLabel.vue';
  import InputErrors from './InputErrors.vue';
  import SubmitValidationErrors from './SubmitValidationErrors.vue';

  const props = defineProps({
    class: {
      type: String,
      default: '',
    },
    hasFormRow: {
      type: Boolean,
      default: true,
    },
    helpText: {
      type: String,
    },
    isDisabled: {
      type: Boolean,
      default: false,
    },
    isDisabledMessage: {
      type: String,
      required: false,
    },
    label: {
      type: String,
      required: false,
    },
    name: {
      type: String,
      required: true,
    },
    required: {
      type: Boolean,
      required: false,
      default: true,
    },
    type: {
      type: String,
      required: false,
      default: 'text',
    },
    validators: {
      type: Array,
      required: false,
      default: () => [],
    },
    value: {
      type: String,
      required: false,
      default: '',
    },
  });

  const inputId = `${uniqueId('input')}`;
  const value = ref(props.value);

  watch(() => props.value, (newValue) => {
    value.value = newValue;
  });

  const inputStore = useInputStore(props.name, props.label, value, props.validators);
  const inputClass = computed(() => {
    return {
      'bhr-input-text': true,
      'bhr-input-text--disabled': props.isDisabled,
      'bhr-input-text--invalid': inputStore.hasVisibleErrors,
      [props.class]: true,
    };
  });
  const formRowClass = computed(() => {
    return {
      'bhr-form-row': props.hasFormRow,
      'bhr-form-row--invalid': props.hasFormRow && inputStore.hasVisibleErrors,
    };
  });
  const ariaDescribedBy = computed(() => useInputAriaDescribedBy(inputId, props.helpText, inputStore.hasVisibleErrors));

  inject('form').addInput(inputStore);
</script>

<template>
  <div :class="formRowClass">

    <FormLabel v-if="props.label" :for="inputId" :required="props.required">
      {{ props.label }}
    </FormLabel>

    <FormHelp
      :inputId="inputId"
      v-if="props.helpText"
    >{{ props.helpText }}</FormHelp>

    <InputErrors
      :errors="inputStore.errors"
      :inputId="inputId"
      :value="value"
      v-if="inputStore.hasVisibleErrors"
    />

    <SubmitValidationErrors
      :errors="inputStore.submitValidationErrors"
      v-if="inputStore.hasVisibleErrors"
    />

    <input
      @blur="inputStore.markAsTouched"
      :aria-describedby="ariaDescribedBy"
      :aria-disabled="props.isDisabled"
      :aria-invalid="inputStore.hasVisibleErrors"
      :class="inputClass"
      :disabled="props.isDisabled"
      :id="inputId"
      :name="props.name"
      :type="props.type"
      :required="props.required"
      v-model="value"
    />

    <p class="sr-only" aria-live="assertive">
      <template v-if="props.isDisabled">
        {{ props.isDisabledMessage }}
      </template>
    </p>
  </div>
</template>
