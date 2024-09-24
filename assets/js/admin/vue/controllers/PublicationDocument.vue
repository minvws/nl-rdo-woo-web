<script setup>
  import Alert from '@admin-fe/component/Alert.vue';
  import Dialog from '@admin-fe/component/Dialog.vue';
  import PublicationDocumentForm from '@admin-fe/component/publication/PublicationDocumentForm.vue';
  import UploadedAttachment from '@admin-fe/component/publication/UploadedAttachment.vue';
  import { findDocumentTypeLabelByValue } from '@admin-fe/component/publication/helper';
  import { isSuccessStatusCode } from '@js/admin/utils';
  import { computed, ref } from 'vue';

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
    canDelete: {
      type: Boolean,
      default: false,
    },
    documentLanguageOptions: {
      type: Array,
      required: true,
      default: () => [],
    },
    documentType: {
      type: String,
      required: true,
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
    uploadGroupId: {
      type: String,
    },
  });

  const createEmptyDocument = () => ({
    formalDate: '',
    internalReference: '',
    grounds: [],
    name: '',
    language: 'Dutch',
    type: '',
  });

  const action = ref(null);
  const addDocumentButton = ref(null);
  const currentDocument = ref(createEmptyDocument());
  const document = ref(null);
  const hasDocument = computed(() => document.value !== null);
  const isDialogOpen = ref(false);
  const isEditMode = computed(() => Boolean(document.value));
  const dialogTitle = computed(() => isEditMode.value ? `${props.documentType} bewerken` : `${props.documentType} toevoegen`);
  const readableDocumentType = computed(() => document.value ? findDocumentTypeLabelByValue(props.documentTypeOptions, document.value.type) : props.documentType);

  const onCancel = () => {
    isDialogOpen.value = false;
  };

  const onDeleted = () => {
    setAction('deleted');
    currentDocument.value = { ...createEmptyDocument() };
    document.value = null;
    isDialogOpen.value = false;
  };

  const onEdit = () => {
    resetAction();
    currentDocument.value = { ...document.value };
    isDialogOpen.value = true;
  };

  const onAddDocument = () => {
    resetAction();
    currentDocument.value = { ...createEmptyDocument() };
    isDialogOpen.value = true;
  };

  const onSaved = (relatedDocument) => {
    setAction(isEditMode.value ? 'edited' : 'added');
    document.value = { ...relatedDocument };
    isDialogOpen.value = false;
  };

  const setAction = (value) => {
    action.value = value;
  };

  const resetAction = () => {
    setAction(null);
  };

  const translateAction = () => {
    const mappings = {
      added: 'toegevoegd',
      deleted: 'verwijderd',
      edited: 'bijgewerkt',
    };

    return mappings[action.value] || '';
  }

  const retrieveDocument = async () => {
    try {
      const response = await fetch(props.endpoint, { headers: { 'Content-Type': 'application/json', accept: 'application/json' } });
      if (isSuccessStatusCode(response.status)) {
        const documentFromApi = await response.json();
        document.value = { ...documentFromApi }
      }
    } catch (error) {}
  }

  retrieveDocument();
</script>

<template>
  <UploadedAttachment
    v-if="hasDocument"

    @deleted="onDeleted"
    @edit="onEdit"

    :can-delete="props.canDelete"
    :date="document.formalDate"
    :document-type="document.type"
    :document-types="props.documentTypeOptions"
    :endpoint="props.endpoint"
    :file-name="document.name"
    :file-size="document.size"
    :id="document.id"
    :mimeType="document.mimeType"
  />

  <div class="py-2" v-if="action">
    <Alert type="success">
      {{ readableDocumentType }} {{ translateAction() }}.
    </Alert>
  </div>

  <button
    v-if="!hasDocument"
    @click="onAddDocument"
    aria-haspopup="dialog"
    class="bhr-button bhr-button--secondary"
    ref="addDocumentButton"
    type="button"
  >
    + {{ props.documentType }} toevoegen...
  </button>

  <Teleport to="body">
    <Dialog v-model="isDialogOpen" :title="dialogTitle">
      <PublicationDocumentForm
        @cancel="onCancel"
        @saved="onSaved"
        :allowed-file-types="props.allowedFileTypes"
        :allowed-mime-types="props.allowedMimeTypes"
        :document="currentDocument"
        :document-language-options="props.documentLanguageOptions"
        :document-type-options="props.documentTypeOptions"
        :endpoint="props.endpoint"
        :ground-options="props.groundOptions"
        :is-edit-mode="isEditMode"
        :upload-group-id="props.uploadGroupId"
      />
    </Dialog>
  </Teleport>
</template>
