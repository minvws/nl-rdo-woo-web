<script setup lang="ts">
import Alert from '@admin-fe/component/Alert.vue';
import Form from '@admin-fe/component/form/Form.vue';
import FormButton from '@admin-fe/component/form/FormButton.vue';
import type { FileInfo } from '@admin-fe/component/form/interface';
import { useFormStore } from '@admin-fe/composables';
import { validators, type FormValue } from '@admin-fe/form';
import type { SelectOptions } from '@admin-fe/form/interface';
import { computed, nextTick, ref, watch } from 'vue';
import InputDate from './input/InputDate.vue';
import InputFileUpload from './input/InputFileUpload.vue';
import InputLanguages from './input/InputLanguages.vue';
import InputFileTypes from './input/InputFileTypes.vue';
import InputGrounds from './input/InputGrounds.vue';
import InputReference from './input/InputReference.vue';
import {
  publicationFileSchema,
  type GroundOptions,
  type PublicationFileTypes,
  type PublicationFile,
} from './interface';

interface Props {
  allowedFileTypes: string[];
  allowedMimeTypes: string[];
  document: PublicationFile;
  endpoint: string;
  fileTypeOptions: PublicationFileTypes;
  groundOptions: GroundOptions;
  isEditMode?: boolean;
  languageOptions: SelectOptions;
  uploadGroupId?: string;
}

interface Emits {
  cancel: [];
  saved: [PublicationFile];
}

const props = withDefaults(defineProps<Props>(), {
  allowedFileTypes: () => [],
  allowedMimeTypes: () => [],
  groundOptions: () => [],
  isEditMode: false,
  languageOptions: () => [],
});

const emit = defineEmits<Emits>();

const fileInfo = ref<FileInfo | null>(null);
const formalDate = computed(() => props.document.formalDate);
const grounds = computed(() => props.document.grounds);
const internalReference = computed(() => props.document.internalReference);
const language = computed(() => props.document.language);
const type = computed(() => props.document.type);

const hasSubmitError = ref(false);

const createFileInfo = (): FileInfo | null => {
  if (!props.document) {
    return null;
  }

  const { name, size, mimeType } = props.document;
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

const onSubmit = async (formValue: FormValue, dirtyFormValue: FormValue) => {
  unsetError();

  return fetch(props.endpoint, {
    body: JSON.stringify(props.isEditMode ? dirtyFormValue : formValue),
    headers: {
      'Content-Type': 'application/json',
      accept: 'application/json',
    },
    method: props.isEditMode ? 'PUT' : 'POST',
  });
};

const onSubmitSuccess = (publicationFile: PublicationFile) => {
  emit('saved', publicationFile);
};

const onSubmitError = () => {
  hasSubmitError.value = true;
};

const formStore = useFormStore(onSubmit, publicationFileSchema);

watch(
  () => [props.document, props.isEditMode],
  async ([, isEditMode]) => {
    fileInfo.value = createFileInfo();
    unsetError();

    await nextTick(); // wait for the inputs to be updated
    formStore.reset();

    const uploadUuidInputStore = formStore.getInputStore('uploadUuid');
    if (!uploadUuidInputStore) {
      return;
    }

    if (isEditMode) {
      uploadUuidInputStore.setValidators([]);
      uploadUuidInputStore.setValue(undefined);
    } else {
      uploadUuidInputStore.setValidators([validators.required()]);
    }
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
      :display-max-one-file-message="true"
    />

    <InputReference :value="internalReference" />

    <InputFileTypes :options="props.fileTypeOptions" :value="type" />

    <InputLanguages :options="props.languageOptions" :value="language" />

    <InputDate :value="formalDate" />

    <InputGrounds :options="props.groundOptions" :values="grounds" />

    <div v-if="hasSubmitError" class="mb-6">
      <Alert type="danger">
        Het opslaan van het document is mislukt. Probeer het later opnieuw.
      </Alert>
    </div>

    <FormButton>Opslaan</FormButton>
    <FormButton @click="cancel" :is-secondary="true">Annuleren</FormButton>
  </Form>
</template>
