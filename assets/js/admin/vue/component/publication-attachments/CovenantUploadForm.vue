
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
  import InputGrounds from './InputGrounds.vue';
  import InputReference from './InputReference.vue';

  const emit = defineEmits(['cancel', 'saved']);

  const props = defineProps({
    covenant: {
      type: Object,
      required: true,
    },
    documentLanguageOptions: {
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
  });

  const fileInfo = computed(() => createFileInfo());
  const formalDate = computed(() => props.covenant.formalDate);
  const grounds = computed(() => props.covenant.grounds);
  const internalReference = computed(() => props.covenant.internalReference);
  const language = computed(() => props.covenant.language);
  const name = computed(() => props.covenant.name);

  const hasSubmitError = ref(false);
  const isFileNameFieldDisabled = ref(false);

  const createFileInfo = () => {
    if (!props.covenant) {
      return null
    }

    const { name, size, mimeType } = props.covenant;
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

    return fetch(props.endpoint, {
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

  watch(() => [props.covenant, props.isEditMode], async ([,isEditMode]) => {
    fileInfo.value = createFileInfo();
    unsetError();

    await nextTick(); // wait for the inputs to be updated
    formStore.reset();

    const inputStore = formStore.getInputStore('uploadUuid');
    if (!inputStore) {
      return;
    }

    if (isEditMode) {
      inputStore.setValidators([]);
      inputStore.setValue(undefined);
    } else {
      inputStore.setValidators([validators.required()]);
    }
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
      :file-info="fileInfo"
      group-id="covenant-documents"
    />

    <InputDocumentName
      :is-disabled="isFileNameFieldDisabled"
      :value="name"
    />

    <InputReference
      :value="internalReference"
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
        Het opslaan van het document is mislukt. Probeer het later opnieuw.
      </Alert>
    </div>

    <FormButton>Opslaan</FormButton>
    <FormButton @click="cancel" :is-secondary="true">Annuleren</FormButton>
  </Form>
</template>
