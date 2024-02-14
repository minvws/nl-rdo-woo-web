<script setup>
  import { computed, ref } from 'vue';
  import UploadedAttachment from '@admin-fe/component/decision-attachments/UploadedAttachment.vue';

  const emit = defineEmits(['deleted', 'edit']);
  const props = defineProps({
    attachments: {
      type: Array,
      required: true,
      default: () => [],
    },
    documentTypes: {
      type: Array,
      required: true,
      default: () => [],
    },
  });

  const numberOfAttachments = computed(() => props.attachments.size);
  const haAttachments = computed(() => numberOfAttachments.value > 0);
  const hasMultipleAttachments = computed(() => numberOfAttachments.value > 1);

  const isFirstAttachment = (index) => index === 0;
  const isLastAttachment = (index) => index === numberOfAttachments.value - 1;

  const getDocumentType = (documentType) => {
    const foundDocumentType = props.documentTypes.find((currentDocumentType) => currentDocumentType.id === documentType);
    return foundDocumentType?.label ?? '';
  };

  const onDeleted = (id) => {
    emit('deleted', id);
  };

  const onEdit = (id) => {
    emit('edit', id);
  };
</script>

<template>
  <div v-if="haAttachments">
    <UploadedAttachment
      v-for="([id, attachment], index) in [...attachments]"

      @deleted="onDeleted"
      @edit="onEdit"

      :can-delete="true"
      :class="{
        '!rounded-b-none': hasMultipleAttachments && isFirstAttachment(index),
        '!rounded-none': hasMultipleAttachments && !isFirstAttachment(index) && !isLastAttachment(index),
        '!rounded-t-none': hasMultipleAttachments && isLastAttachment(index),
        '-mt-px': hasMultipleAttachments && !isFirstAttachment(index),
      }"
      :id="attachment.id"
      :date="attachment.date"
      :documentType="getDocumentType(attachment.documentType)"
      :fileName="attachment.fileName"
      :fileSize="attachment.fileSize"
      :key="attachment.id"
      :mimeType="attachment.mimeType"
    />
  </div>
</template>
