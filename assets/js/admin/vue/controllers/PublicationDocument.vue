<script setup lang="ts">
import Alert from '@admin-fe/component/Alert.vue';
import Dialog from '@admin-fe/component/Dialog.vue';
import { findFileTypeLabelByValue } from '@admin-fe/component/publication/file/helper';
import type { SelectOptions } from '@admin-fe/form/interface';
import {
  publicationFileSchema,
  type GroundOptions,
  type PublicationFile,
  type PublicationFileTypes,
} from '@admin-fe/component/publication/file/interface';
import PublicationDocumentForm from '@admin-fe/component/publication/file/PublicationDocumentForm.vue';
import PublicationFileItem from '@admin-fe/component/publication/file/PublicationFileItem.vue';
import { validateResponse } from '@js/admin/utils';
import { computed, ref } from 'vue';

interface Props {
  allowedFileTypes: string[];
  allowedMimeTypes: string[];
  canDelete: boolean;
  documentType: string;
  endpoint: string;
  fileTypeOptions: PublicationFileTypes;
  groundOptions: GroundOptions;
  languageOptions: SelectOptions;
  uploadGroupId: string;
}

const props = withDefaults(defineProps<Props>(), {
  allowedFileTypes: () => [],
  allowedMimeTypes: () => [],
  canDelete: false,
  languageOptions: () => [],
  fileTypeOptions: () => [],
  groundOptions: () => [],
});

const createEmptyDocument = (): PublicationFile => ({
  dossier: {
    id: '',
  },
  formalDate: '',
  internalReference: '',
  grounds: [],
  name: '',
  language: 'Dutch',
  mimeType: '',
  size: 0,
  type: '',
});

const action = ref<string | null>(null);
const addDocumentButton = ref<HTMLButtonElement | null>(null);
const currentDocument = ref<PublicationFile>(createEmptyDocument());
const document = ref<PublicationFile | null>(null);
const hasDocument = computed(() => document.value !== null);
const isDialogOpen = ref(false);
const isEditMode = computed(() => Boolean(document.value));
const dialogTitle = computed(() =>
  isEditMode.value
    ? `${props.documentType} bewerken`
    : `${props.documentType} toevoegen`,
);
const readablePublicationFileType = computed(() =>
  document.value
    ? findFileTypeLabelByValue(props.fileTypeOptions, document.value.type)
    : props.documentType,
);

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
  currentDocument.value = { ...document.value } as PublicationFile;
  isDialogOpen.value = true;
};

const onAddDocument = () => {
  resetAction();
  currentDocument.value = { ...createEmptyDocument() };
  isDialogOpen.value = true;
};

const onSaved = (relatedDocument: PublicationFile) => {
  setAction(isEditMode.value ? 'edited' : 'added');
  document.value = { ...relatedDocument };
  isDialogOpen.value = false;
};

const setAction = (value: string | null) => {
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

  return mappings[action.value as keyof typeof mappings] || '';
};

const retrieveDocument = async () => {
  try {
    const request = fetch(props.endpoint, {
      headers: {
        'Content-Type': 'application/json',
        accept: 'application/json',
      },
    });

    const documentFromApi = await validateResponse(
      request,
      publicationFileSchema,
    );

    document.value = { ...documentFromApi };
    // eslint-disable-next-line @typescript-eslint/no-unused-vars, no-empty
  } catch (error) {}
};

retrieveDocument();
</script>

<template>
  <PublicationFileItem
    v-if="hasDocument"
    @deleted="onDeleted"
    @edit="onEdit"
    :can-delete="props.canDelete"
    :date="document?.formalDate as string"
    :endpoint="props.endpoint"
    :file-name="document?.name as string"
    :file-size="document?.size as number"
    :file-types="props.fileTypeOptions"
    :file-type-value="document?.type as string"
    :id="document?.id as string"
    :mime-type="document?.mimeType as string"
  />

  <div class="py-2" v-if="action">
    <Alert type="success">
      {{ readablePublicationFileType }} {{ translateAction() }}.
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
        :endpoint="props.endpoint"
        :file-type-options="props.fileTypeOptions"
        :ground-options="props.groundOptions"
        :is-edit-mode="isEditMode"
        :language-options="props.languageOptions"
        :upload-group-id="props.uploadGroupId"
      />
    </Dialog>
  </Teleport>
</template>
