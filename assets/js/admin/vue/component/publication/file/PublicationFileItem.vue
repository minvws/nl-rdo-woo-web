<script setup lang="ts">
import Alert from '@admin-fe/component/Alert.vue';
import Pending from '@admin-fe/component/Pending.vue';
import UploadedFile from '@admin-fe/component/file/UploadedFile.vue';
import {
  formatFileSize,
  getFileTypeByMimeType,
  isSuccessStatusCode,
} from '@js/admin/utils';
import { formatDate } from '@utils';
import { computed, ref, useTemplateRef } from 'vue';
import { findFileTypeLabelByValue } from './helper';
import { PublicationFileTypes } from './interface';

interface Props {
  canDelete: boolean;
  class?: Record<string, boolean> | string;
  id: string;
  date: string;
  endpoint: string;
  fileName: string;
  fileSize: number;
  fileTypes: PublicationFileTypes;
  fileTypeValue: string;
  mimeType: string;
  withdrawUrl?: string;
}

interface Emits {
  deleted: [string];
  deleteError: [string];
  edit: [string];
}

const props = withDefaults(defineProps<Props>(), {
  canDelete: false,
  class: () => ({}),
});

const emit = defineEmits<Emits>();

const editButtonElement = useTemplateRef<HTMLButtonElement>('editButton');
const hasDeleteError = ref(false);
const isPending = ref(false);
const formattedDate = computed(() => formatDate(props.date));
const formattedFileSize = computed(() => formatFileSize(props.fileSize));
const formattedFileType = computed(() => getFileTypeByMimeType(props.mimeType));
const readablePublicationFileType = computed(() =>
  findFileTypeLabelByValue(props.fileTypes, props.fileTypeValue),
);

const onDelete = async () => {
  isPending.value = true;

  const response = await fetch(props.endpoint, {
    method: 'DELETE',
    headers: { 'Content-Type': 'application/json', accept: 'application/json' },
  });

  isPending.value = false;

  if (isSuccessStatusCode(response.status)) {
    emit('deleted', props.id);
    return;
  }

  hasDeleteError.value = true;
};

const onEdit = () => {
  emit('edit', props.id);
};

defineExpose({
  setFocus: () => {
    editButtonElement.value?.focus();
  },
});
</script>

<template>
  <Pending :is-pending="isPending">
    <Alert v-if="hasDeleteError" type="danger">
      Het verwijderen van "{{ props.fileName }}" is mislukt. Probeer het later
      opnieuw.
    </Alert>

    <UploadedFile
      v-else
      @delete="onDelete"
      :canDelete="props.canDelete"
      :class="props.class"
      :fileName="props.fileName"
      :fileSize="props.fileSize"
      :mimeType="props.mimeType"
      :withdraw-url="props.withdrawUrl"
    >
      {{ readablePublicationFileType }} - {{ formattedDate }} ({{
        formattedFileType
      }}, {{ formattedFileSize }})
      <template #extra>
        <button
          @click="onEdit"
          aria-haspopup="dialog"
          class="bhr-a"
          :class="{ 'pr-4': !props.canDelete && !props.withdrawUrl }"
          data-e2e-name="edit-file"
          ref="editButtonElement"
          type="button"
        >
          <span class="sr-only">{{ props.fileName }}</span> Aanpassen
        </button>
      </template>
    </UploadedFile>
  </Pending>
</template>
