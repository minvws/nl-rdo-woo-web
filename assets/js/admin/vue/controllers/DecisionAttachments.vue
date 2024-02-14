<script setup>
  import { computed, ref } from 'vue';
  import Dialog from '../component/Dialog.vue';
  import DecisionAttachmentForm from '@admin-fe/component/decision-attachments/AttachmentForm.vue';
  import AttachmentsList from '@admin-fe/component/decision-attachments/AttachmentsList.vue';

  const props = defineProps({
    attachments: {
      type: Array,
      required: true,
      default: () => [
        {
          id: 1,
          date: '2021-01-01',
          documentType: 1,
          fileName: 'Bestandsnaampie',
          fileSize: 1024 * 1024,
          mimeType: 'application/pdf',
        },
        {
          id: 2,
          date: '2021-04-06',
          documentType: 2,
          fileName: 'Uitspraak beroep procedure',
          fileSize: 1024 * 1024 * 3.4,
          mimeType: 'application/zip',
        },
        {
          id: 3,
          date: '2023-04-06',
          documentType: 2,
          fileName: 'Joe joe',
          fileSize: (1024 * 1024) / 0.83,
          mimeType: 'application/pdf',
        },
      ],
    },
    documentTypes: {
      type: Array,
      required: true,
      default: () => [
        { id: 1, label: 'Beslissing op bezwaar' },
        { id: 2, label: 'Andere optie' },
      ],
    },
  });

  const addAttachmentButton = ref(null);

  const collectAttachments = (attachments) => attachments.reduce((collection, attachment) => {
    const { id } = attachment;
    if (!collection.has(id)) {
      collection.set(id, attachment);
    }
    return collection;
  }, new Map());

  const createEmptyAttachment = () => ({
    id: null,
    date: null,
    documentType: null,
    fileName: null,
    fileSize: null,
    mimeType: null,
  });

  const attachments = ref(collectAttachments(props.attachments));
  const currentAttachment = ref(createEmptyAttachment());
  const haAttachments = computed(() => attachments.value.size > 0);
  const isDialogOpen = ref(false);
  const isEditMode = computed(() => currentAttachment.value.id !== null);
  const dialogTitle = computed(() => isEditMode.value ? 'Bijlage bewerken' : 'Bijlage toevoegen');

  const onCancel = () => {
    isDialogOpen.value = false;
  };

  const onDeleted = (id) => {
    attachments.value.delete(id);
    addAttachmentButton.value?.focus();
  };

  const onEdit = (id) => {
    currentAttachment.value = { ...attachments.value.get(id) };
    isDialogOpen.value = true;
  };

  const onAddAttachment = (attachment) => {
    currentAttachment.value = createEmptyAttachment();
    isDialogOpen.value = true;
  };

  const onSaved = (attachment) => {
    currentAttachment.value = { ...attachment};
    attachments.value.set(attachment.id, attachment);
    isDialogOpen.value = false;
  };
</script>

<template>
  <AttachmentsList
    @deleted="onDeleted"
    @edit="onEdit"
    :attachments="attachments"
    :documentTypes="props.documentTypes"
    class="pb-2"
  />

  <button
    @click="onAddAttachment"
    aria-haspopup="dialog"
    class="bhr-button bhr-button--secondary"
    ref="addAttachmentButton"
    type="button"
  >
    + {{ haAttachments ? 'Nog een bijlage' : 'Bijlage' }} toevoegen...
  </button>

  <Teleport to="body">
    <Dialog v-model="isDialogOpen" :title="dialogTitle">
      <DecisionAttachmentForm
        @cancel="onCancel"
        @saved="onSaved"
        :attachment="currentAttachment"
        :documentTypes="props.documentTypes"
        :is-edit-mode="isEditMode"
      />
    </Dialog>
  </Teleport>
</template>
