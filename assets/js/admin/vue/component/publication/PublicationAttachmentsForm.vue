<script setup>
  import Alert from '@admin-fe/component/Alert.vue';
  import Form from '@admin-fe/component/form/Form.vue';
  import FormButton from '@admin-fe/component/form/FormButton.vue';
  import { useFormStore } from '@admin-fe/composables';
  import { validators } from '@admin-fe/form';
  import { computed, nextTick, ref, watch } from 'vue';
  import InputDocumentDate from './InputDocumentDate.vue';
  import InputDocumentFile from './InputDocumentFile.vue';
  import InputDocumentLanguages from './InputDocumentLanguages.vue';
  import InputDocumentName from './InputDocumentName.vue';
  import InputDocumentTypes from './InputDocumentTypes.vue';
  import InputGrounds from './InputGrounds.vue';
  import InputReference from './InputReference.vue';

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
    attachment: {
      type: Object,
      required: true,
    },
    documentLanguageOptions: {
      type: Array,
      required: true,
      default: () => [],
    },
    documentTypeOptions: {
      type: Array,
      required: true,
      default: () => [],
    },
    endpoint: {
      type: String,
      required: true,
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
    uploadGroupId: {
      type: String,
    },
  });

  const id = computed(() => props.attachment.id);

  const fileInfo = computed(() => createFileInfo());
  const formalDate = computed(() => props.attachment.formalDate);
  const grounds = computed(() => props.attachment.grounds);
  const internalReference = computed(() => props.attachment.internalReference);
  const language = computed(() => props.attachment.language);
  const name = computed(() => props.attachment.name);
  const type = computed(() => props.attachment.type);

  const hasSubmitError = ref(false);
  const isFileNameFieldDisabled = ref(false);

  const createFileInfo = () => {
    if (!props.attachment) {
      return null
    }

    const { name, size, mimeType } = props.attachment;
    if (!name || !size || !mimeType) {
      return null;
    }

    return { name, size, type: mimeType };
  }

  const unsetError = () => {
    hasSubmitError.value = false;
  }

  const cancel = () => {
    emit('cancel');
    unsetError();
  }

  const onSubmit = (formValue, dirtyFormValue) => {
    unsetError();

    const endpoint = props.isEditMode ? `${props.endpoint}/${id.value}` : props.endpoint;
    return fetch(endpoint, {
      body: JSON.stringify(props.isEditMode ? dirtyFormValue : formValue),
      headers: { 'Content-Type': 'application/json', accept: 'application/json' },
      method: props.isEditMode ? 'PUT' : 'POST',
    });
  }

  const onSubmitSuccess = (attachment) => {
    emit('saved', attachment);
  }

  const onSubmitError = (response) => {
    hasSubmitError.value = true;
  }

  const formStore = useFormStore(onSubmit);

  const onUploaded = (file) => {
    isFileNameFieldDisabled.value = false;
    const nameInputStore = formStore.getInputStore('name');
    if (nameInputStore && !nameInputStore.value) {
      nameInputStore.setValue(file.name);
    }
  }

  const onUploading = () => {
    isFileNameFieldDisabled.value = true;
  }

  const onUploadError = () => {
    isFileNameFieldDisabled.value = false;
  }

  watch(() => [props.attachment.id, props.isEditMode], async ([,isEditMode]) => {
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
  });
</script>

<template>
  <Form
    @pristineSubmit="cancel"
    @submitError="onSubmitError"
    @submitSuccess="onSubmitSuccess"
    :store="formStore"
  >
    <InputDocumentFile
      @uploaded="onUploaded"
      @uploadError="onUploadError"
      @uploading="onUploading"
      :allowed-file-types="props.allowedFileTypes"
      :allowed-mime-types="props.allowedMimeTypes"
      :file-info="fileInfo"
      :group-id="props.uploadGroupId"
    />

    <InputDocumentName
      :is-disabled="isFileNameFieldDisabled"
      :value="name"
    />

    <InputReference
      :value="internalReference"
    />

    <InputDocumentTypes
      :options="props.documentTypeOptions"
      :value="type"
    />

    <InputDocumentLanguages
      :options="props.documentLanguageOptions"
      :value="language"
    />

    <InputDocumentDate
      :value="formalDate"
    />

    <InputGrounds
      :options="props.groundOptions"
      :values="grounds"
    />

    <div v-if="hasSubmitError" class="mb-6">
      <Alert type="danger">
        Het opslaan van de bijlage is mislukt. Probeer het later opnieuw.
      </Alert>
    </div>

    <FormButton>Opslaan</FormButton>
    <FormButton @click="cancel" :is-secondary="true">Annuleren</FormButton>
  </Form>
</template>
