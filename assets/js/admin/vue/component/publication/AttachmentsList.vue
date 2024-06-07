<script setup>
  import { computed } from 'vue';
  import { collectAllSelectOptionsFromDocumentTypes } from './helper';
  import UploadedAttachment from './UploadedAttachment.vue';

  const emit = defineEmits(['deleted', 'deleteError', 'edit']);
  const props = defineProps({
    attachments: {
      type: Array,
      required: true,
      default: () => [],
    },
    canDelete: {
      type: Boolean,
      required: true,
      default: false,
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
  });

  const numberOfAttachments = computed(() => props.attachments.size);
  const hasAttachments = computed(() => numberOfAttachments.value > 0);
  const hasMultipleAttachments = computed(() => numberOfAttachments.value > 1);

  const createDeleteEndpoint = (id) => `${props.endpoint}/${id}`;

  const isFirstAttachment = (index) => index === 0;
  const isLastAttachment = (index) => index === numberOfAttachments.value - 1;

  const onDeleted = (id) => {
    emit('deleted', id);
  };

  const onEdit = (id) => {
    emit('edit', id);
  };
</script>

<template>
  <div v-if="hasAttachments">
    <UploadedAttachment
      v-for="([id, attachment], index) in [...attachments]"

      @deleted="onDeleted"
      @edit="onEdit"

      :can-delete="props.canDelete"
      :class="{
        '!rounded-b-none': hasMultipleAttachments && isFirstAttachment(index),
        '!rounded-none': hasMultipleAttachments && !isFirstAttachment(index) && !isLastAttachment(index),
        '!rounded-t-none': hasMultipleAttachments && isLastAttachment(index),
        '-mt-px': hasMultipleAttachments && !isFirstAttachment(index),
      }"
      :id="attachment.id"
      :date="attachment.formalDate"
      :document-type="attachment.type"
      :document-types="props.documentTypes"
      :endpoint="createDeleteEndpoint(attachment.id)"
      :file-name="attachment.name"
      :file-size="attachment.size"
      :key="attachment.id"
      :mimeType="attachment.mimeType"
    />
  </div>
</template>
