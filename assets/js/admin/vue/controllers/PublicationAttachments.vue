<script setup>
  import Alert from '@admin-fe/component/Alert.vue';
  import Dialog from '@admin-fe/component/Dialog.vue';
  import AttachmentsList from '@admin-fe/component/publication-attachments/AttachmentsList.vue';
  import PublicationAttachmentsForm from '@admin-fe/component/publication-attachments/PublicationAttachmentsForm.vue';
  import { computed, ref } from 'vue';

  const props = defineProps({
    canDelete: {
      type: Boolean,
      required: true,
      default: false,
    },
    documentLanguageOptions: {
      type: Array,
      required: true,
      default: () => [],
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
    groupId: {
      type: String,
      required: true,
      default: 'default',
    },
  });

  const createEmptyAttachment = () => ({
    internalReference: '',
    language: 'Dutch',
    name: '',
    formalDate: '',
    type: '',
    grounds: [],
  });

  const addAttachmentButton = ref(null);
  const attachments = ref(new Map());
  const updatedAttachment = ref({ attachment: null, action: null });
  const currentAttachment = ref(createEmptyAttachment());
  const hasAttachments = computed(() => attachments.value.size > 0);
  const isDialogOpen = ref(false);
  const isEditMode = computed(() => Boolean(currentAttachment.value.id));
  const dialogTitle = computed(() => isEditMode.value ? 'Bijlage bewerken' : 'Bijlage toevoegen');

  const resetUpdatedAttachment = () => {
    updatedAttachment.value = { attachment: null, action: null };
  };

  const onCancel = () => {
    isDialogOpen.value = false;
  };

  const onDeleted = (id) => {
    const deletedAttachment = { ...attachments.value.get(id) };
    attachments.value.delete(id);
    addAttachmentButton.value?.focus();
    updatedAttachment.value = { attachment: deletedAttachment, action: 'deleted' };
  };

  const onEdit = (id) => {
    resetUpdatedAttachment();
    currentAttachment.value = { ...attachments.value.get(id) };
    isDialogOpen.value = true;
  };

  const onAddAttachment = (attachment) => {
    resetUpdatedAttachment();
    currentAttachment.value = createEmptyAttachment();
    isDialogOpen.value = true;
  };

  const onSaved = (attachment) => {
    updatedAttachment.value = { attachment: attachment, action: isEditMode.value ? 'updated' : 'created' };
    currentAttachment.value = { ...attachment };
    attachments.value.set(attachment.id, attachment);
    isDialogOpen.value = false;
  };

  const retrieveAttachments = async () => {
    try {
      const response = await fetch(props.endpoint, { headers: { 'Content-Type': 'application/json', accept: 'application/json' } });
      const attachmentsFromApi = await response.json();
      attachmentsFromApi.forEach((attachment) => {
        attachments.value.set(attachment.id, attachment);
      });
    } catch (error) {}
  }

  retrieveAttachments();
</script>

<template>
  <AttachmentsList
    @deleted="onDeleted"
    @edit="onEdit"
    :attachments="attachments"
    :can-delete="props.canDelete"
    :documentTypes="props.documentTypeOptions"
    :endpoint="props.endpoint"
    class="pb-2"
  />

  <div class="pb-2" v-if="updatedAttachment.attachment">
    <Alert type="success">
      Bijlage '{{ updatedAttachment.attachment.name }}' is {{ updatedAttachment.action === 'deleted' ? 'verwijderd' : (updatedAttachment.action === 'created' ? 'toegevoegd' : 'bijgewerkt') }}.
    </Alert>
  </div>

  <button
    @click="onAddAttachment"
    aria-haspopup="dialog"
    class="bhr-button bhr-button--secondary"
    ref="addAttachmentButton"
    type="button"
  >
    + {{ hasAttachments ? 'Nog een bijlage' : 'Bijlage' }} toevoegen...
  </button>

  <Teleport to="body">
    <Dialog v-model="isDialogOpen" :title="dialogTitle">
      <PublicationAttachmentsForm
        @cancel="onCancel"
        @saved="onSaved"
        :attachment="currentAttachment"
        :document-language-options="props.documentLanguageOptions"
        :document-type-options="props.documentTypeOptions"
        :endpoint="props.endpoint"
        :ground-options="props.groundOptions"
        :group-id="props.groupId"
        :is-edit-mode="isEditMode"
      />
    </Dialog>
  </Teleport>
</template>
