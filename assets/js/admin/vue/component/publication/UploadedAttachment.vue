<script setup>
import UploadedFile from '@admin-fe/component/file/UploadedFile.vue';
import {
  formatFileSize,
  getFileTypeByMimeType,
  isSuccessStatusCode,
} from '@js/admin/utils';
import { formatDate } from '@utils';
import { computed, ref } from 'vue';
import Alert from '../Alert.vue';
import Pending from '../Pending.vue';
import { findDocumentTypeLabelByValue } from './helper';

const emit = defineEmits(['deleted', 'deleteError', 'edit']);
const props = defineProps({
  canDelete: {
    type: Boolean,
    required: false,
    default: false,
  },
  class: {
    type: String,
    required: false,
    default: '',
  },
  id: {
    type: String,
    required: true,
  },
  isFirst: {
    type: Boolean,
    required: false,
    default: false,
  },
  isLast: {
    type: Boolean,
    required: false,
    default: false,
  },
  date: {
    type: String,
    required: true,
  },
  documentType: {
    type: String,
    required: true,
  },
  documentTypes: {
    type: Array,
    required: true,
    default: () => [],
  },
  endpoint: {
    type: String,
    required: true,
  },
  fileName: {
    type: String,
    required: true,
  },
  fileSize: {
    type: Number,
    required: true,
  },
  mimeType: {
    type: String,
    required: true,
  },
});

const hasDeleteError = ref(false);
const isPending = ref(false);
const formattedDate = computed(() => formatDate(props.date));
const formattedFileSize = computed(() => formatFileSize(props.fileSize));
const formattedFileType = computed(() => getFileTypeByMimeType(props.mimeType));
const readableDocumentType = computed(() =>
  findDocumentTypeLabelByValue(props.documentTypes, props.documentType),
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
</script>

<template>
  <Pending :is-pending="isPending">
    <Alert v-if="hasDeleteError" type="danger">
      Het verwijderen van de bijlage "{{ props.fileName }}" is mislukt. Probeer
      het later opnieuw.
    </Alert>

    <UploadedFile
      v-else
      @delete="onDelete"
      :canDelete="props.canDelete"
      :class="props.class"
      :fileName="props.fileName"
      :fileSize="props.fileSize"
      :mimeType="props.mimeType"
    >
      {{ readableDocumentType }} - {{ formattedDate }} ({{ formattedFileType }},
      {{ formattedFileSize }})
      <template #extra>
        <button
          @click="onEdit"
          aria-haspopup="dialog"
          class="bhr-a"
          :class="{ 'pr-4': !props.canDelete }"
          type="button"
        >
          <span class="sr-only">{{ props.fileName }}</span> Aanpassen
        </button>
      </template>
    </UploadedFile>
  </Pending>
</template>
