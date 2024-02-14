<script setup>
  import { computed, inject, ref, watch } from 'vue';
  import { uniqueId } from '@js/utils';
  import FormHelp from './FormHelp.vue';
  import FormLabel from './FormLabel.vue';
  import InputErrors from './InputErrors.vue';
  import { useInputAriaDescribedBy, useInputStore } from '@admin-fe/composables';

  const props = defineProps({
    class: {
      type: String,
      required: false,
      default: '',
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
    required: {
      type: Boolean,
      required: false,
      default: false,
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

  const form = inject('form');
  form.addInput(inputStore);
</script>

<template>
  <div :class="formRowClass">

    <FormLabel v-if="props.label" :for="inputId">
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
