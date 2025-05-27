<script setup>
import { useInputAriaDescribedBy, useInputStore } from '@admin-fe/composables';
import { uniqueId } from '@js/utils';
import { computed, inject, ref, watch } from 'vue';
import ErrorMessages from './ErrorMessages.vue';
import FormHelp from './FormHelp.vue';
import FormLabel from './FormLabel.vue';
import InputErrors from './InputErrors.vue';

const props = defineProps({
  emptyLabel: {
    type: String,
    required: false,
    default: 'Kies een optie',
  },
  hasFormRow: {
    type: Boolean,
    required: false,
    default: true,
  },
  helpText: {
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
  options: {
    type: Array,
    required: true,
    default: () => [],
  },
  optgroups: {
    type: Array,
    required: false,
    default: () => [],
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
const inputClass = computed(() => {
  return {
    'bhr-select__select': true,
    'bhr-select__select--invalid': inputStore.hasVisibleErrors,
    'w-full sm:w-auto sm:min-w-[50%]': true,
  };
});
const formRowClass = computed(() => {
  return {
    'bhr-form-row': props.hasFormRow,
    'bhr-form-row--invalid': props.hasFormRow && inputStore.hasVisibleErrors,
  };
});
const ariaDescribedBy = computed(() =>
  useInputAriaDescribedBy(inputId, props.helpText, inputStore.hasVisibleErrors),
);

inject('form').addInput(inputStore);
</script>

<template>
  <div :class="formRowClass">
    <FormLabel v-if="props.label" :for="inputId">
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
        :errors="inputStore.submitValidationErrors"
        v-if="inputStore.hasVisibleErrors"
      />
    </div>

    <div class="bhr-select">
      <select
        @change="inputStore.markAsTouched"
        :aria-describedby="ariaDescribedBy"
        :aria-invalid="inputStore.hasVisibleErrors"
        :class="inputClass"
        :id="inputId"
        :name="props.name"
        v-model="value"
      >
        <option value="">{{ props.emptyLabel }}</option>
        <option
          v-for="option in props.options"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
        <optgroup
          v-for="optgroup in props.optgroups"
          :key="optgroup.label"
          :label="optgroup.label"
        >
          <option
            v-for="option in optgroup.options"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </optgroup>
      </select>
    </div>
  </div>
</template>
