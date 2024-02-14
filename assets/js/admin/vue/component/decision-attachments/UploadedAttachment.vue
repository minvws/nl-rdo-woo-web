<script setup>
  import UploadedFile from '@admin-fe/component/file/UploadedFile.vue';
  import Pending from '../Pending.vue';
  import { formatFileSize, getFileTypeByMimeType } from '@js/admin/utils';
  import { formatDate } from '@utils';
  import { computed, ref } from 'vue';

  const emit = defineEmits(['deleted', 'edit']);
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
    documentTypeId: {
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

  const isPending = ref(false);
  const formattedDate = computed(() => formatDate(props.date));
  const formattedFileSize = computed(() => formatFileSize(props.fileSize));
  const formattedFileType = computed(() => getFileTypeByMimeType(props.mimeType));

  const onDelete = () => {
    isPending.value = true;
    setTimeout(() => {
      isPending.value = false;
      emit('deleted', props.id);
    }, 1200);
  }

  const onEdit = () => {
    emit('edit', props.id);
  }
</script>

<template>
  <Pending :is-pending="isPending">
    <UploadedFile
      @delete="onDelete"
      :canDelete="props.canDelete"
      :class="props.class"
      :fileName="props.fileName"
      :fileSize="props.fileSize"
      :mimeType="props.mimeType"
    >
      {{ props.documentType }} - {{ formattedDate }} ({{ formattedFileType }}, {{ formattedFileSize }})
      <template #extra>
        <button @click="onEdit" aria-haspopup="dialog" class="bhr-a" type="button">
          <span class="sr-only">{{ props.fileName }}</span> Aanpassen
        </button>
      </template>
    </UploadedFile>
  </Pending>
</template>
