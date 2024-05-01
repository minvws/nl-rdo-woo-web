<script setup>
  import { computed, ref } from 'vue';
  import CovenantUploadForm from '@admin-fe/component/publication-attachments/CovenantUploadForm.vue';
  import UploadedAttachment from '@admin-fe/component/publication-attachments/UploadedAttachment.vue';
  import Alert from '@admin-fe/component/Alert.vue';
  import Dialog from '@admin-fe/component/Dialog.vue';
  import { isSuccessStatusCode } from '@js/admin/utils';

  const props = defineProps({
    canDelete: {
      type: Boolean,
      default: false,
    },
    documentLanguageOptions: {
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
  });

  const createEmptyCovenant = () => ({
    formalDate: '',
    internalReference: '',
    grounds: [],
    name: '',
    language: 'Dutch',
  });

  const addCovenantButton = ref(null);
  const covenant = ref(null);
  const currentCovenant = ref(createEmptyCovenant());
  const hasCovenant = computed(() => covenant.value !== null);
  const isDialogOpen = ref(false);
  const isEditMode = computed(() => Boolean(covenant.value));
  const dialogTitle = computed(() => isEditMode.value ? 'Convenant bewerken' : 'Convenant toevoegen');
  const action = ref(null);

  const onCancel = () => {
    isDialogOpen.value = false;
  };

  const onDeleted = () => {
    setAction('deleted');
    currentCovenant.value = { ...createEmptyCovenant() };
    covenant.value = null;
    isDialogOpen.value = false;
  };

  const onEdit = () => {
    resetAction();
    currentCovenant.value = { ...covenant.value };
    isDialogOpen.value = true;
  };

  const onAddCovenant = () => {
    resetAction();
    currentCovenant.value = { ...createEmptyCovenant() };
    isDialogOpen.value = true;
  };

  const onSaved = (relatedConvenant) => {
    setAction(isEditMode.value ? 'edited' : 'added');
    covenant.value = { ...relatedConvenant };
    isDialogOpen.value = false;
  };

  const setAction = (value) => {
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

    return mappings[action.value] || '';
  }

  const retrieveCovenant = async () => {
    try {
      const response = await fetch(props.endpoint, { headers: { 'Content-Type': 'application/json', accept: 'application/json' } });
      if (isSuccessStatusCode(response.status)) {
        const covenantFromApi = await response.json();
        covenant.value = { ...covenantFromApi }
      }
    } catch (error) {}
  }

  retrieveCovenant();
</script>

<template>
  <UploadedAttachment
    v-if="hasCovenant"

    @deleted="onDeleted"
    @edit="onEdit"

    :can-delete="props.canDelete"
    :date="covenant.formalDate"
    :endpoint="props.endpoint"
    documentType="Convenant"
    :file-name="covenant.name"
    :file-size="covenant.size"
    :mimeType="covenant.mimeType"
  />

  <div class="pb-2" v-if="action">
    <Alert type="success">
      Convenant {{ translateAction() }}.
    </Alert>
  </div>

  <button
    v-if="!hasCovenant"
    @click="onAddCovenant"
    aria-haspopup="dialog"
    class="bhr-button bhr-button--secondary"
    ref="addCovenantButton"
    type="button"
  >
    + Convenant toevoegen...
  </button>

  <Teleport to="body">
    <Dialog v-model="isDialogOpen" :title="dialogTitle">
      <CovenantUploadForm
        @cancel="onCancel"
        @saved="onSaved"
        :covenant="currentCovenant"
        :document-language-options="props.documentLanguageOptions"
        :endpoint="props.endpoint"
        :ground-options="props.groundOptions"
        :is-edit-mode="isEditMode"
      />
    </Dialog>
  </Teleport>
</template>
