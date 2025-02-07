<script setup lang="ts">
import Alert from '@admin-fe/component/Alert.vue';
import UploadArea from '@admin-fe/component/file/upload/UploadArea.vue';
import ErrorMessages from '@admin-fe/component/form/ErrorMessages.vue';
import Icon from '@admin-fe/component/Icon.vue';
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
import UploadedDocuments from './UploadedDocuments.vue';

interface Props {
  allowedFileTypes: string[];
  allowedMimeTypes: string[];
  dossierId: string;
  isComplete: boolean;
  maxFileSize: number;
  processEndpoint: string;
  statusEndpoint: string;
  uploadEndpoint: string;
}

const props = withDefaults(defineProps<Props>(), {
  allowedFileTypes: () => [],
  allowedMimeTypes: () => [],
});

const emit = defineEmits(['onComplete']);

const uploadStatusResponse = ref<WooDecisionUploadStatusResponse | undefined>();

const isComplete = computed(() => {
  if (props.isComplete) {
    return true;
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
const missingDocumentsCount = computed(() => missingDocuments.value.length);

const isProcessing = computed(() => {
  const { value } = uploadStatusResponse;
  if (!value) {
    return false;
  }

  return [
    UploadStatus.ProcessingUpdates,
    UploadStatus.ProcessingUploads,
  ].includes(value.status);
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
  if (isProcessing.value) {
    return false;
  }

  return isComplete.value === false;
});
const uploadAreaComponent = useTemplateRef<ComponentPublicInstance>(
  'uploadAreaComponent',
);
const isProcessingElement = useTemplateRef<HTMLDivElement>(
  'isProcessingElement',
);

const { focused: isFocusWithinIsProcessingElement } =
  useFocusWithin(isProcessingElement);
const { focused: isFocusWithinUploadArea } =
  useFocusWithin(uploadAreaComponent);

const onIsUploading = (value: boolean) => {
  isUploading.value = value;
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

  timeoutId = setTimeout(checkStatus, 2500);
};

if (!props.isComplete) {
  checkStatus();
}

const moveFocus = async () => {
  if (isProcessing.value && isFocusWithinUploadArea.value) {
    await nextTick();
    isProcessingElement.value?.focus();
    return;
  }

  if (!isProcessing.value && isFocusWithinIsProcessingElement.value) {
    const currentElementWithFocus = document.activeElement as HTMLElement;
    await nextTick();
    currentElementWithFocus?.focus();
  }
};

onBeforeUnmount(() => stopCheckingStatus());
</script>

<template>
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
    :payload="{ dossierId: props.dossierId, groupId: 'woo-decision-documents' }"
    ref="uploadAreaComponent"
    tip="Tip: je kunt meerdere documenten tegelijkertijd uploaden. Sleep je hele selectie (of een zip-bestand) naar dit venster."
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
    >
      Bestanden verwerken
    </button>
  </UploadArea>

  <output class="block">
    <div
      class="mb-4"
      tabindex="-1"
      ref="isProcessingElement"
      v-if="isProcessing"
    >
      <Alert type="info">
        <h3 class="font-bold">
          Wacht niet langer meer na het uploaden van je bestanden
        </h3>

        <template #extra>
          We zijn nu bezig met het verwerken van de documenten. Dit kan soms wat
          tijd in beslag nemen. Je kunt hier later terugkomen, de documenten
          worden op de achtergrond verwerkt.
        </template>
      </Alert>
    </div>

    <div
      class="bg-bhr-cornsilk px-6 pb-6 max-h-80 overflow-y-auto"
      :class="{ 'mt-4': isUploadAreaVisible }"
      v-if="missingDocumentsCount > 0"
    >
      <Icon
        v-if="isProcessing"
        name="loader"
        :size="32"
        class="animate-spin mt-4"
      />

      <h3 class="mb-3 mt-4 text-lg" data-e2e-name="missingDocuments">
        Nog te uploaden:
        <span class="font-bold">{{ missingDocumentsCount }}</span> van
        {{ expectedDocumentsCount }} document{{
          expectedDocumentsCount !== 1 ? 'en' : ''
        }}.
      </h3>

      <ul class="grid grid-cols-4 gap-x-4">
        <li v-for="file in missingDocuments" :key="file" class="py-1">
          <span class="flex">
            <span class="mr-3">
              <Icon name="file-unknown" :size="20" />
            </span>
            <span class="grow truncate pt-0.5">{{ file }}</span>
          </span>
        </li>
      </ul>
    </div>
  </output>
</template>
