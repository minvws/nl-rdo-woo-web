<script setup lang="ts">
import Alert from '@admin-fe/component/Alert.vue';
import Dialog from '@admin-fe/component/Dialog.vue';
import {
  findFileTypeLabelByValue,
  getValuesFromPublicationFileTypes,
} from '@admin-fe/component/publication/file/helper/types';
import {
  publicationFilesSchema,
  type GroundOptions,
  type PublicationFile,
  type PublicationFileTypes,
} from '@admin-fe/component/publication/file/interface';
import PublicationFileForm from '@admin-fe/component/publication/file/PublicationFileForm.vue';
import PublicationFilesList from '@admin-fe/component/publication/file/PublicationFilesList.vue';
import type { SelectOptions } from '@admin-fe/form/interface';
import { validateResponse } from '@js/admin/utils';
import { computed, ref } from 'vue';

interface Props {
  allowedFileTypes: string[];
  allowedMimeTypes: string[];
  allowMultiple?: boolean;
  canDelete: boolean;
  endpoint: string;
  fileTypeOptions: PublicationFileTypes;
  groundOptions: GroundOptions;
  languageOptions: SelectOptions;
  uploadGroupId: string;
}

const props = withDefaults(defineProps<Props>(), {
  allowedFileTypes: () => [],
  allowedMimeTypes: () => [],
  allowMultiple: true,
  canDelete: false,
  fileTypeOptions: () => [],
  groundOptions: () => [],
  languageOptions: () => [],
});

const createEmptyPublicationFile = (): PublicationFile => ({
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

const documentTypeValues = getValuesFromPublicationFileTypes(
  props.fileTypeOptions,
);

const readablePublicationFileType =
  documentTypeValues.length === 1
    ? findFileTypeLabelByValue(props.fileTypeOptions, documentTypeValues[0])
    : 'Bijlage';

const addFileButton = ref<HTMLButtonElement | null>(null);
const currentFile = ref<PublicationFile>(createEmptyPublicationFile());
const files = ref<Map<string, PublicationFile>>(new Map());
const isDialogOpen = ref(false);
const isOnlyOneAllowedMessageVisible = ref(false);
const updatedFile = ref<{
  action: 'created' | 'deleted' | 'updated' | null;
  file: PublicationFile | null;
}>({ action: null, file: null });

const hasFiles = computed(() => files.value.size > 0);
const isEditMode = computed(() => Boolean(currentFile.value.id));
const dialogTitle = computed(() =>
  isEditMode.value
    ? `${readablePublicationFileType} bewerken`
    : `${readablePublicationFileType} toevoegen`,
);

const getFileById = (id: string) => files.value.get(id) as PublicationFile;

const resetOnlyOneAllowedMessageVisibility = () => {
  isOnlyOneAllowedMessageVisible.value = false;
};

const resetUpdatedFile = () => {
  updatedFile.value = { action: null, file: null };
};

const onCancel = () => {
  isDialogOpen.value = false;
};

const onDeleted = (id: string) => {
  const deletedFile = { ...getFileById(id) };
  files.value.delete(id);
  addFileButton.value?.focus();
  updatedFile.value = {
    action: 'deleted',
    file: deletedFile,
  };
  resetOnlyOneAllowedMessageVisibility();
};

const onEdit = (id: string) => {
  resetUpdatedFile();
  currentFile.value = { ...getFileById(id) };
  isDialogOpen.value = true;
};

const onAddFile = () => {
  if (!props.allowMultiple && hasFiles.value) {
    isOnlyOneAllowedMessageVisible.value = true;
    return;
  }

  resetUpdatedFile();
  currentFile.value = createEmptyPublicationFile();
  isDialogOpen.value = true;
};

const onDialogClose = () => {
  resetOnlyOneAllowedMessageVisibility();
};

const onSaved = (file: PublicationFile) => {
  updatedFile.value = {
    file: file,
    action: isEditMode.value ? 'updated' : 'created',
  };
  currentFile.value = { ...file };
  files.value.set(file.id as string, file);
  isDialogOpen.value = false;
};

const retrieveFiles = async () => {
  try {
    const request = fetch(props.endpoint, {
      headers: {
        'Content-Type': 'application/json',
        accept: 'application/json',
      },
    });
    const filesFromApi = await validateResponse(
      request,
      publicationFilesSchema,
    );
    filesFromApi.forEach((file) => {
      if (!documentTypeValues.includes(file.type)) {
        return;
      }
      files.value.set(file.id as string, file);
    });
    // eslint-disable-next-line @typescript-eslint/no-unused-vars, no-empty
  } catch (error) {}
};

retrieveFiles();
</script>

<template>
  <PublicationFilesList
    @deleted="onDeleted"
    @edit="onEdit"
    :can-delete="props.canDelete"
    :publicationFileTypes="props.fileTypeOptions"
    :endpoint="props.endpoint"
    :files="files"
    class="pb-2"
  />

  <div class="pb-2" v-if="updatedFile.file">
    <Alert type="success">
      {{ readablePublicationFileType }} '{{ updatedFile.file.name }}' is
      {{
        updatedFile.action === 'deleted'
          ? 'verwijderd'
          : updatedFile.action === 'created'
            ? 'toegevoegd'
            : 'bijgewerkt'
      }}.
    </Alert>
  </div>

  <div class="mb-1" v-if="isOnlyOneAllowedMessageVisible">
    <Alert type="danger">
      Je kunt hier maximaal 1
      {{ readablePublicationFileType.toLowerCase() }} opgeven.
    </Alert>
  </div>

  <button
    @click="onAddFile"
    aria-haspopup="dialog"
    class="bhr-button bhr-button--secondary"
    ref="addFileButton"
    type="button"
  >
    +
    {{
      hasFiles
        ? `Nog een ${readablePublicationFileType.toLowerCase()}`
        : readablePublicationFileType
    }}
    toevoegen...
  </button>

  <Teleport to="body">
    <Dialog v-model="isDialogOpen" @close="onDialogClose" :title="dialogTitle">
      <PublicationFileForm
        @cancel="onCancel"
        @saved="onSaved"
        :allowed-file-types="props.allowedFileTypes"
        :allowed-mime-types="props.allowedMimeTypes"
        :endpoint="props.endpoint"
        :file="currentFile"
        :file-type-options="props.fileTypeOptions"
        :ground-options="props.groundOptions"
        :is-edit-mode="isEditMode"
        :language-options="props.languageOptions"
        :upload-group-id="props.uploadGroupId"
      />
    </Dialog>
  </Teleport>
</template>
