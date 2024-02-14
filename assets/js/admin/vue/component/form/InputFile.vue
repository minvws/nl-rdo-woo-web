<script setup>
  import { computed, inject, ref, watch } from 'vue';
  import { uniqueId } from '@js/utils';
  import FormHelp from './FormHelp.vue';
  import FormLabel from './FormLabel.vue';
  import InputErrors from './InputErrors.vue';
  import UploadArea from '../file/upload/UploadArea.vue';
  import { useInputAriaDescribedBy, useInputStore } from '@admin-fe/composables';

  const props = defineProps({
    allowedMimeTypes: {
      type: Array,
      required: false,
      default: () => [],
    },
    allowMultiple: {
      type: Boolean,
      required: false,
      default: false,
    },
    endpoint: {
      type: String,
      required: false,
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
    maxFileSize: {
      type: Number,
      required: false,
    },
    name: {
      type: String,
      required: true,
    },
    tip: {
      type: String,
      required: true,
    },
  });

  const inputId = `${uniqueId('input')}`;
  const value = ref(new DataTransfer().files);
  const formRowClass = computed(() => {
    return {
      'bhr-form-row': props.hasFormRow,
      'bhr-form-row--invalid': props.hasFormRow && inputStore.hasVisibleErrors,
    };
  });
  const inputStore = useInputStore(props.name, props.label, value, props.validators);
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

    <UploadArea
      :allow-multiple="props.allowMultiple"
      :allowed-mime-types="props.allowedMimeTypes"
      :endpoint="props.endpoint"
      :id="inputId"
      :max-file-size="props.maxFileSize"
      :name="props.name"
      :tip="props.tip"
    />
  </div>
</template>
