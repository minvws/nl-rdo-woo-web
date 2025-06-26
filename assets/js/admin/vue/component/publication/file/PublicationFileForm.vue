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
import InputFileTypes from './input/InputFileTypes.vue';
import InputFileUpload from './input/InputFileUpload.vue';
import InputGrounds from './input/InputGrounds.vue';
import InputLanguages from './input/InputLanguages.vue';
import InputReference from './input/InputReference.vue';
import {
  publicationFileSchema,
  type GroundOptions,
  type PublicationFile,
  type PublicationFileTypes,
} from './interface';

interface Props {
  allowedFileTypes: string[];
  allowedMimeTypes: string[];
  allowMultiple: boolean;
  dateLabel?: string;
  endpoint: string;
  file: PublicationFile;
  fileTypeLabel: string;
  fileTypeOptions: PublicationFileTypes;
  groundOptions: GroundOptions;
  isEditMode?: boolean;
  languageOptions: SelectOptions;
  uploadGroupId: string;
  dossierId?: null | string;
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

const id = computed(() => props.file.id);

const fileInfo = ref<FileInfo | null>(null);
const formalDate = computed(() => props.file.formalDate);
const grounds = computed(() => props.file.grounds);
const internalReference = computed(() => props.file.internalReference);
const language = computed(() => props.file.language);
const type = computed(() => props.file.type);

const hasSubmitError = ref(false);

const createFileInfo = (): FileInfo | null => {
  if (!props.file) {
    return null;
  }

  const { name, size, mimeType } = props.file;
  if (!name || !size || !mimeType) {
    return null;
  }

  return { name, size, type: mimeType };
};

const saveButtonText = computed(
  () =>
    `Opslaan en ${props.fileTypeLabel.toLowerCase()} ${props.isEditMode ? 'bijwerken' : 'toevoegen'}`,
);

const unsetError = () => {
  hasSubmitError.value = false;
};

const cancel = () => {
  emit('cancel');
  unsetError();
};

const onSubmit = (formValue: FormValue, dirtyFormValue: FormValue) => {
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

const onSubmitSuccess = (publicationFile: unknown) => {
  emit('saved', publicationFile as PublicationFile);
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
      :display-max-one-file-message="!props.allowMultiple"
      :file-info="fileInfo"
      :group-id="props.uploadGroupId"
      :dossier-id="props.dossierId"
    />

    <InputReference :value="internalReference" />

    <InputFileTypes :options="props.fileTypeOptions" :value="type" />

    <InputLanguages :options="props.languageOptions" :value="language" />

    <InputDate :label="props.dateLabel" :value="formalDate" />

    <InputGrounds :options="props.groundOptions" :values="grounds" />

    <div v-if="hasSubmitError" class="mb-6" data-e2e-name="save-failed">
      <Alert type="danger">
        Het opslaan van "{{ file.name }}" is mislukt. Probeer het later opnieuw.
      </Alert>
    </div>

    <FormButton>{{ saveButtonText }}</FormButton>
    <FormButton @click="cancel" :is-secondary="true">Annuleren</FormButton>
  </Form>
</template>
