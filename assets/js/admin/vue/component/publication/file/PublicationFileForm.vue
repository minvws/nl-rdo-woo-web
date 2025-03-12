<script setup>
import Alert from '@admin-fe/component/Alert.vue';
import Form from '@admin-fe/component/form/Form.vue';
import FormButton from '@admin-fe/component/form/FormButton.vue';
import { useFormStore } from '@admin-fe/composables';
import { validators } from '@admin-fe/form';
import { computed, nextTick, ref, watch } from 'vue';
import InputDate from './input/InputDate.vue';
import InputFileUpload from './input/InputFileUpload.vue';
import InputLanguages from './input/InputLanguages.vue';
import InputFileTypes from './input/InputFileTypes.vue';
import InputGrounds from './input/InputGrounds.vue';
import InputReference from './input/InputReference.vue';
import { publicationFileSchema } from './interface';

const emit = defineEmits(['cancel', 'saved']);

const props = defineProps({
  allowedFileTypes: {
    type: Array,
    required: true,
    default: () => [],
  },
  allowedMimeTypes: {
    type: Array,
    required: true,
    default: () => [],
  },
  endpoint: {
    type: String,
    required: true,
  },
  file: {
    type: Object,
    required: true,
  },
  fileTypeOptions: {
    type: Array,
    required: true,
    default: () => [],
  },
  groundOptions: {
    type: Array,
    required: true,
    default: () => [],
  },
  isEditMode: {
    type: Boolean,
    default: false,
  },
  languageOptions: {
    type: Array,
    required: true,
    default: () => [],
  },
  uploadGroupId: {
    type: String,
  },
});

const id = computed(() => props.file.id);

const fileInfo = computed(() => createFileInfo());
const formalDate = computed(() => props.file.formalDate);
const grounds = computed(() => props.file.grounds);
const internalReference = computed(() => props.file.internalReference);
const language = computed(() => props.file.language);
const type = computed(() => props.file.type);

const hasSubmitError = ref(false);

const createFileInfo = () => {
  if (!props.file) {
    return null;
  }

  const { name, size, mimeType } = props.file;
  if (!name || !size || !mimeType) {
    return null;
  }

  return { name, size, type: mimeType };
};

const unsetError = () => {
  hasSubmitError.value = false;
};

const cancel = () => {
  emit('cancel');
  unsetError();
};

const onSubmit = (formValue, dirtyFormValue) => {
  unsetError();

  const endpoint = props.isEditMode
    ? `${props.endpoint}/${id.value}`
    : props.endpoint;
  return fetch(endpoint, {
    body: JSON.stringify(props.isEditMode ? dirtyFormValue : formValue),
    headers: { 'Content-Type': 'application/json', accept: 'application/json' },
    method: props.isEditMode ? 'PUT' : 'POST',
  });
};

const onSubmitSuccess = (file) => {
  emit('saved', file);
};

const onSubmitError = () => {
  hasSubmitError.value = true;
};

const formStore = useFormStore(onSubmit, publicationFileSchema);

watch(
  () => [props.file.id, props.isEditMode],
  async ([, isEditMode]) => {
    fileInfo.value = createFileInfo();
    unsetError();

    await nextTick(); // wait for the inputs to be updated
    formStore.reset();

    const fileUploadInputStore = formStore.getInputStore('uploadUuid');
    if (!fileUploadInputStore) {
      return;
    }

    const fileUploadValidators = isEditMode ? [] : [validators.required()];
    fileUploadInputStore.setValidators(fileUploadValidators);
  },
);
</script>

<template>
  <Form
    @pristineSubmit="cancel"
    @submitError="onSubmitError"
    @submitSuccess="onSubmitSuccess"
    :store="formStore"
  >
    <InputFileUpload
      :allowed-file-types="props.allowedFileTypes"
      :allowed-mime-types="props.allowedMimeTypes"
      :file-info="fileInfo"
      :group-id="props.uploadGroupId"
    />

    <InputReference :value="internalReference" />

    <InputFileTypes :options="props.fileTypeOptions" :value="type" />

    <InputLanguages :options="props.languageOptions" :value="language" />

    <InputDate :value="formalDate" />

    <InputGrounds :options="props.groundOptions" :values="grounds" />

    <div v-if="hasSubmitError" class="mb-6">
      <Alert type="danger">
        Het opslaan van de bijlage is mislukt. Probeer het later opnieuw.
      </Alert>
    </div>

    <FormButton>Opslaan</FormButton>
    <FormButton @click="cancel" :is-secondary="true">Annuleren</FormButton>
  </Form>
</template>
