<script setup>
import UploadArea from '@admin-fe/component/file/upload/UploadArea.vue';
import { useInputStore } from '@admin-fe/composables';
import { uniqueId } from '@js/utils';
import { computed, inject, ref } from 'vue';
import ErrorMessages from './ErrorMessages.vue';
import FormHelp from './FormHelp.vue';
import FormLabel from './FormLabel.vue';
import InputErrors from './InputErrors.vue';

const emit = defineEmits(['uploaded', 'uploadError']);

const props = defineProps({
  allowedFileTypes: {
    type: Array,
    required: true,
    default: () => [],
  },
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
  enableAutoUpload: {
    type: Boolean,
    default: false,
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
  payload: {
    type: Object,
  },
  tip: {
    type: String,
    required: false,
  },
  uploadId: {
    type: String,
    required: false,
  },
  uploadedFileInfo: {
    type: [Object, null],
    required: false,
    default: null,
  },
  validators: {
    type: Array,
    required: false,
    default: () => [],
  },
  value: {
    type: [Object, String],
    required: false,
  },
});

const getValueFromProps = () => {
  if (props.value) {
    return props.value;
  }

  if (props.uploadId) {
    return props.uploadId;
  }

  if (props.allowMultiple) {
    return new DataTransfer().files;
  }

  return '';
};

const inputId = `${uniqueId('input')}`;
const valueRef = ref(getValueFromProps());
const formRowClass = computed(() => {
  return {
    'bhr-form-row': props.hasFormRow,
    'bhr-form-row--invalid': props.hasFormRow && inputStore.hasVisibleErrors,
  };
});

const onUploaded = (file, uploadId) => {
  valueRef.value = uploadId;
  emit('uploaded', file, uploadId);
};

const onUploadError = (fileId, file) => {
  emit('uploadError', fileId, file);
};

const inputStore = useInputStore(
  props.name,
  props.label,
  valueRef,
  props.validators,
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

    <UploadArea
      @uploaded="onUploaded"
      @uploadError="onUploadError"
      :allow-multiple="props.allowMultiple"
      :allowed-file-types="props.allowedFileTypes"
      :allowed-mime-types="props.allowedMimeTypes"
      :enable-auto-upload="props.enableAutoUpload"
      :id="inputId"
      :max-file-size="props.maxFileSize"
      :name="props.name"
      :payload="props.payload"
      :tip="props.tip"
      :uploaded-file-info="props.uploadedFileInfo"
    />
  </div>
</template>
