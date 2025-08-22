<script setup lang="ts">
import Alert from '@admin-fe/component/Alert.vue';
import ErrorMessages from '@admin-fe/component/form/ErrorMessages.vue';
import type { FileUploadLimit } from '@js/admin/utils/file/interface';
import { ref } from 'vue';
import UploadDocuments from './UploadDocuments.vue';

interface Props {
  dossierId: string;
  fileLimits: FileUploadLimit[];
  isComplete: boolean;
  confirmEndpoint: string;
  rejectEndpoint: string;
  processEndpoint: string;
  statusEndpoint: string;
  uploadEndpoint: string;
  nextStepUrl: string;
  continueLaterUrl: string;
}

const props = withDefaults(defineProps<Props>(), {
  fileLimits: () => [],
});

const showCannotContinueMessage = ref(false);
const isComplete = ref(Boolean(props.isComplete));

const clickNextStep = (event: Event) => {
  if (isComplete.value) {
    return;
  }

  event.preventDefault();
  showCannotContinueMessage.value = true;
};

const onComplete = () => {
  isComplete.value = true;
  showCannotContinueMessage.value = false;
};
</script>

<template>
  <UploadDocuments
    @on-complete="onComplete"
    :dossier-id="props.dossierId"
    :is-complete="props.isComplete"
    :file-limits="props.fileLimits"
    mode="add"
    :confirm-endpoint="props.confirmEndpoint"
    :reject-endpoint="props.rejectEndpoint"
    :process-endpoint="props.processEndpoint"
    :status-endpoint="props.statusEndpoint"
    :upload-endpoint="props.uploadEndpoint"
  />

  <Alert v-if="isComplete" type="success" data-e2e-name="upload-completed">
    <strong>Uploaden gelukt:</strong> Alle documenten uit het productierapport
    zijn geüpload.
  </Alert>

  <div class="mt-4">
    <div aria-live="assertive">
      <ErrorMessages
        :messages="[
          'Nog niet alle documenten zijn geüpload of verwerkt. Voeg ze toe of wacht tot ze verwerkt zijn om verder te gaan.',
        ]"
        v-if="showCannotContinueMessage"
      />
    </div>

    <a
      @click="clickNextStep"
      :href="props.nextStepUrl"
      class="bhr-btn-filled-primary mr-4"
      data-e2e-name="to-next-step-link"
    >
      Verder naar publiceren
    </a>

    <a
      :href="props.continueLaterUrl"
      class="bhr-btn-bordered-primary"
      data-e2e-name="continue-later"
    >
      Later verdergaan
    </a>
  </div>
</template>
