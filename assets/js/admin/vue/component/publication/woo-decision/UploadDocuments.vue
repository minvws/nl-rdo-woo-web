<script setup lang="ts">
import UploadArea from '@admin-fe/component/file/upload/UploadArea.vue';
import ErrorMessages from '@admin-fe/component/form/ErrorMessages.vue';
import { validateResponse } from '@js/admin/utils';
import { useFocusWithin } from '@vueuse/core';
import {
  computed,
  nextTick,
  onBeforeUnmount,
  ref,
  useTemplateRef,
  type ComponentPublicInstance,
} from 'vue';
import {
  UploadStatus,
  WooDecisionUploadStatusResponse,
  wooDecisionUploadStatusResponseSchema,
} from './interface';
import DocumentChanges from './DocumentChanges.vue';
import IsCheckingDocuments from './IsCheckingDocuments.vue';
import IsProcessingDocuments from './IsProcessingDocuments.vue';
import MissingDocuments from './MissingDocuments.vue';
import UploadedDocuments from './UploadedDocuments.vue';

interface Props {
  allowedFileTypes: string[];
  allowedMimeTypes: string[];
  dossierId: string;
  isComplete: boolean;
  maxFileSize: number;
  mode?: 'add' | 'replace';
  confirmEndpoint: string;
  rejectEndpoint: string;
  processEndpoint: string;
  statusEndpoint: string;
  uploadEndpoint: string;
}

const props = withDefaults(defineProps<Props>(), {
  allowedFileTypes: () => [],
  allowedMimeTypes: () => [],
  mode: 'replace',
});

const emit = defineEmits(['onComplete']);
const isAddMode = props.mode === 'add';
const isReplaceMode = !isAddMode;

const uploadStatusResponse = ref<WooDecisionUploadStatusResponse | undefined>();

const isComplete = computed(() => {
  if (props.isComplete) {
    return true;
  }

  if (isReplaceMode) {
    return false;
  }

  const { value } = uploadStatusResponse;
  if (!value) {
    return false;
  }

  if (value.status === UploadStatus.Completed) {
    return true;
  }

  return value.currentDocumentsCount >= value.expectedDocumentsCount;
});

const expectedDocumentsCount = computed(
  () => uploadStatusResponse.value?.expectedDocumentsCount ?? 0,
);
const missingDocuments = computed(
  () => uploadStatusResponse.value?.missingDocuments ?? [],
);
const showIsChecking = computed(
  () => uploadStatusResponse.value?.status === UploadStatus.ProcessingUploads,
);
const showDocumentChanges = computed(
  () => uploadStatusResponse.value?.status === UploadStatus.NeedsConfirmation,
);

const showIsProcessing = computed(() => {
  const { value } = uploadStatusResponse;
  if (!value) {
    return false;
  }

  return [UploadStatus.Confirmed, UploadStatus.ProcessingUpdates].includes(
    value.status,
  );
});

const showMissingDocuments = computed(() => {
  if (showIsChecking.value) {
    return false;
  }

  return missingDocuments.value.length > 0;
});

const isUploading = ref(false);

const canProcess = computed(() => {
  if (isUploading.value) {
    return false;
  }

  return uploadStatusResponse.value?.canProcess ?? false;
});

const showCannotProcessMessage = ref(false);
const cannotProcessMessage = computed(() => {
  if (isUploading.value) {
    return 'Bestanden kunnen worden verwerkt wanneer er momenteel geen bestanden worden geüpload.';
  }

  return 'Bestanden kunnen worden verwerkt wanneer er minstens één bestand is geüpload.';
});
const uploadedFiles = computed(
  () => uploadStatusResponse.value?.uploadedFiles ?? [],
);
const isUploadAreaVisible = computed(() => {
  if (
    showIsChecking.value ||
    showIsProcessing.value ||
    showDocumentChanges.value
  ) {
    return false;
  }

  return isComplete.value === false;
});
const wrapperElement = useTemplateRef<HTMLDivElement>('wrapperElement');
const uploadAreaComponent = useTemplateRef<ComponentPublicInstance>(
  'uploadAreaComponent',
);
const processButtonElement = useTemplateRef<HTMLButtonElement>(
  'processButtonElement',
);
const isCheckingComponent = useTemplateRef<
  InstanceType<typeof IsCheckingDocuments>
>('isCheckingComponent');
const isProcessingComponent = useTemplateRef<
  InstanceType<typeof IsProcessingDocuments>
>('isProcessingComponent');

const { focused: isFocusWithinComponent } = useFocusWithin(wrapperElement);

const onIsUploading = (value: boolean) => {
  isUploading.value = value;

  if (value) {
    stopCheckingStatus();
    return;
  }

  checkStatus();
};

const processFiles = async () => {
  if (!canProcess.value) {
    showCannotProcessMessage.value = true;
    return;
  }

  await fetch(props.processEndpoint, { method: 'POST' });
  await checkStatus();
};

let timeoutId: ReturnType<typeof setTimeout>;

const stopCheckingStatus = () => {
  clearTimeout(timeoutId);
};

const checkStatus = async () => {
  stopCheckingStatus();

  const response = await validateResponse(
    fetch(props.statusEndpoint),
    wooDecisionUploadStatusResponseSchema,
  );

  uploadStatusResponse.value = response;

  if (canProcess.value) {
    showCannotProcessMessage.value = false;
  }

  await moveFocus();

  if (isComplete.value) {
    emit('onComplete');
    return;
  }

  if (showDocumentChanges.value) {
    return;
  }

  timeoutId = setTimeout(checkStatus, 1250);
};

if (!props.isComplete) {
  checkStatus();
}

const moveFocus = async () => {
  if (!isFocusWithinComponent.value) {
    return;
  }

  if (showIsChecking.value) {
    await nextTick();
    isCheckingComponent.value?.setFocus();
    return;
  }

  if (showIsProcessing.value) {
    await nextTick();
    isProcessingComponent.value?.setFocus();
    return;
  }

  const currentElementWithFocus = document.activeElement as HTMLElement;
  await nextTick();

  if (currentElementWithFocus) {
    currentElementWithFocus.focus();
    return;
  }
  processButtonElement.value?.focus();
};

onBeforeUnmount(() => stopCheckingStatus());
</script>

<template>
  <div ref="wrapperElement">
    <label
      v-if="isUploadAreaVisible"
      class="sr-only"
      for="upload-area-dossier-files"
    >
      Upload documenten
    </label>

    <UploadArea
      v-if="isUploadAreaVisible"
      @is-uploading="onIsUploading"
      :allow-multiple="true"
      :allowed-file-types="props.allowedFileTypes"
      :allowed-mime-types="props.allowedMimeTypes"
      :enable-auto-upload="true"
      :endpoint="props.uploadEndpoint"
      id="upload-area-dossier-files"
      :max-file-size="props.maxFileSize"
      :payload="{
        dossierId: props.dossierId,
        groupId: 'woo-decision-documents',
      }"
      ref="uploadAreaComponent"
    >
      <output class="block">
        <UploadedDocuments :files="uploadedFiles" />

        <ErrorMessages
          v-if="showCannotProcessMessage"
          :messages="[cannotProcessMessage]"
        />
      </output>

      <button
        @click="processFiles"
        class="bhr-button bhr-button--primary"
        ref="processButtonElement"
        type="button"
        data-e2e-name="process-documents"
        :data-e2e-is-uploading="isUploading"
      >
        Bestanden verwerken
      </button>
    </UploadArea>

    <output class="block">
      <DocumentChanges
        v-if="showDocumentChanges"
        @go-back="checkStatus"
        :add="uploadStatusResponse?.changes.add"
        :republish="uploadStatusResponse?.changes.republish"
        :update="uploadStatusResponse?.changes.update"
        :confirm-endpoint="props.confirmEndpoint"
        :reject-endpoint="props.rejectEndpoint"
      />

      <IsProcessingDocuments
        v-if="showIsProcessing"
        ref="isProcessingComponent"
      />

      <IsCheckingDocuments v-if="showIsChecking" ref="isCheckingComponent" />

      <div :class="{ 'mt-4': isUploadAreaVisible }" v-if="showMissingDocuments">
        <MissingDocuments
          :documents="missingDocuments"
          :expected-count="expectedDocumentsCount"
          :is-processing="showIsProcessing"
        />
      </div>
    </output>
  </div>
</template>
