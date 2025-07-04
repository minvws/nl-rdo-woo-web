<script setup lang="ts">
import Alert from '@admin-fe/component/Alert.vue';
import Dialog from '@admin-fe/component/Dialog.vue';
import Icon from '@admin-fe/component/Icon.vue';
import type { SelectOptions } from '@admin-fe/form/interface';
import { validateResponse } from '@js/admin/utils';
import { useFocusWithin } from '@vueuse/core';
import { computed, nextTick, useTemplateRef, ref, provide } from 'vue';
import {
  findFileTypeLabelByValue,
  getValuesFromPublicationFileTypes,
} from './helper/types';
import {
  publicationFilesSchema,
  type GroundOptions,
  type PublicationFile,
  type PublicationFileTypes,
} from './interface';
import PublicationFileForm from './PublicationFileForm.vue';
import PublicationFilesList from './PublicationFilesList.vue';
import { UPLOAD_AREA_ENDPOINT } from '../../../../vue/component/file/upload/static';

interface Props {
  allowedFileTypes: string[];
  allowedMimeTypes: string[];
  allowMultiple?: boolean;
  canDelete: boolean;
  dateLabel?: string;
  endpoint: string;
  uploadEndpoint?: null | string;
  e2eName?: string;
  fileTypeOptions: PublicationFileTypes;
  groundOptions: GroundOptions;
  languageOptions: SelectOptions;
  readableFileType?: string;
  uploadGroupId: string;
  dossierId?: null | string;
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

if (props.uploadEndpoint) {
  provide(UPLOAD_AREA_ENDPOINT, props.uploadEndpoint);
}

const getReadableFileType = () => {
  if (props.readableFileType) {
    return props.readableFileType;
  }

  if (fileTypeValues.length === 1) {
    return findFileTypeLabelByValue(props.fileTypeOptions, fileTypeValues[0]);
  }

  return 'Bijlage';
};

const fileTypeValues = getValuesFromPublicationFileTypes(props.fileTypeOptions);
const fileTypeLabel = getReadableFileType();
const addFileButtonElement = useTemplateRef<HTMLButtonElement>('addFileButton');
const wrapperElement = useTemplateRef<HTMLDivElement>('wrapper');
const publicationFilesListComponent = useTemplateRef<
  typeof PublicationFilesList
>('publicationFilesList');

const { focused: isFocusWithinWrapper } = useFocusWithin(wrapperElement);

const currentFile = ref<PublicationFile>(createEmptyPublicationFile());
const files = ref<Map<string, PublicationFile>>(new Map());
const isDialogOpen = ref(false);
const updatedFile = ref<{
  action: 'created' | 'deleted' | 'updated' | null;
  file: PublicationFile | null;
}>({ action: null, file: null });

const hasFiles = computed(() => files.value.size > 0);
const isAddFileButtonVisible = computed(() =>
  props.allowMultiple ? true : !hasFiles.value,
);
const isEditMode = computed(() => Boolean(currentFile.value.id));
const dialogTitle = computed(() =>
  isEditMode.value ? `${fileTypeLabel} bewerken` : `${fileTypeLabel} toevoegen`,
);

const getFileById = (id: string) => files.value.get(id) as PublicationFile;

const resetUpdatedFile = () => {
  updatedFile.value = { action: null, file: null };
};

const onCancel = () => {
  isDialogOpen.value = false;
};

const onDeleted = (id: string) => {
  const deletedFile = { ...getFileById(id) };
  files.value.delete(id);
  updatedFile.value = {
    action: 'deleted',
    file: deletedFile,
  };

  resetFocus();
};

const onEdit = (id: string) => {
  resetUpdatedFile();
  currentFile.value = { ...getFileById(id) };
  isDialogOpen.value = true;
};

const onAddFile = () => {
  resetUpdatedFile();
  currentFile.value = createEmptyPublicationFile();
  isDialogOpen.value = true;
};

const onSaved = (file: PublicationFile) => {
  updatedFile.value = {
    file: file,
    action: isEditMode.value ? 'updated' : 'created',
  };
  currentFile.value = { ...file };
  files.value.set(file.id as string, file);
  isDialogOpen.value = false;

  resetFocus();
};

const resetFocus = async () => {
  await nextTick();

  if (isFocusWithinWrapper.value) {
    // Aparently, the element having focus was not removed from the DOM: great.
    return;
  }

  if (addFileButtonElement.value) {
    addFileButtonElement.value.focus();
    return;
  }

  publicationFilesListComponent.value?.setFocus();
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
      if (!fileTypeValues.includes(file.type)) {
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
  <div ref="wrapper">
    <PublicationFilesList
      @deleted="onDeleted"
      @edit="onEdit"
      :can-delete="props.canDelete"
      :publicationFileTypes="props.fileTypeOptions"
      :endpoint="props.endpoint"
      :files="files"
      class="pb-2"
      ref="publicationFilesListComponent"
    />

    <div class="pb-2" data-e2e-name="alerts" v-if="updatedFile.file">
      <Alert type="success">
        {{ fileTypeLabel }} '{{ updatedFile.file.name }}' is
        {{
          updatedFile.action === 'deleted'
            ? 'verwijderd'
            : updatedFile.action === 'created'
              ? 'toegevoegd'
              : 'bijgewerkt'
        }}.
      </Alert>
    </div>

    <button
      v-if="isAddFileButtonVisible"
      @click="onAddFile"
      aria-haspopup="dialog"
      class="bhr-btn-ghost-primary mt-1"
      data-e2e-name="add-file"
      ref="addFileButton"
      type="button"
    >
      <Icon
        class="bhr-btn__icon-left"
        color="fill-current"
        name="plus"
        :size="24"
      />
      {{ hasFiles ? `Nog een ${fileTypeLabel.toLowerCase()}` : fileTypeLabel }}
      toevoegen...
    </button>
  </div>

  <Teleport to="body">
    <Dialog
      v-model="isDialogOpen"
      :e2e-name="props.e2eName"
      :title="dialogTitle"
    >
      <PublicationFileForm
        @cancel="onCancel"
        @saved="onSaved"
        :allowed-file-types="props.allowedFileTypes"
        :allowed-mime-types="props.allowedMimeTypes"
        :allow-multiple="props.allowMultiple"
        :date-label="props.dateLabel"
        :endpoint="props.endpoint"
        :file="currentFile"
        :file-type-label="fileTypeLabel"
        :file-type-options="props.fileTypeOptions"
        :ground-options="props.groundOptions"
        :is-edit-mode="isEditMode"
        :language-options="props.languageOptions"
        :upload-group-id="props.uploadGroupId"
        :dossier-id="props.dossierId"
      />
    </Dialog>
  </Teleport>
</template>
