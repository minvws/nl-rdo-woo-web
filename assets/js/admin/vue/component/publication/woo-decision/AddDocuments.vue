<script setup lang="ts">
import Alert from '@admin-fe/component/Alert.vue';
import ErrorMessages from '@admin-fe/component/form/ErrorMessages.vue';
import { ref } from 'vue';
import UploadDocuments from './UploadDocuments.vue';

interface Props {
  allowedFileTypes: string[];
  allowedMimeTypes: string[];
  dossierId: string;
  expectedUploadCount: number;
  isComplete: boolean;
  maxFileSize: number;
  processEndpoint: string;
  statusEndpoint: string;
  uploadEndpoint: string;
  nextStepUrl: string;
  continueLaterUrl: string;
}

const props = withDefaults(defineProps<Props>(), {
  allowedFileTypes: () => [],
  allowedMimeTypes: () => [],
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
    :allowed-file-types="props.allowedFileTypes"
    :allowed-mime-types="props.allowedMimeTypes"
    :dossier-id="props.dossierId"
    :is-complete="props.isComplete"
    :max-file-size="props.maxFileSize"
    :process-endpoint="props.processEndpoint"
    :status-endpoint="props.statusEndpoint"
    :upload-endpoint="props.uploadEndpoint"
  />

  <Alert v-if="isComplete" type="success" data-e2e-name="upload-completed">
    <strong>Uploaden gelukt:</strong> Alle documenten uit het productierapport
    zijn geüpload.
  </Alert>

  <div class="mt-4">
    <output class="block">
      <ErrorMessages
        :messages="[
          'Nog niet alle documenten zijn geüpload of verwerkt. Voeg ze toe of wacht tot ze verwerkt zijn om verder te gaan.',
        ]"
        v-if="showCannotContinueMessage"
      />
    </output>

    <a
      @click="clickNextStep"
      :href="props.nextStepUrl"
      class="bhr-button bhr-button--primary bhr-button--fixed-width mr-4"
      data-e2e-name="to-next-step-link"
    >
      Verder naar publiceren
    </a>

    <a
      :href="props.continueLaterUrl"
      class="bhr-button bhr-button--secondary bhr-button--fixed-width mr-4"
    >
      Later verdergaan
    </a>
  </div>
</template>
