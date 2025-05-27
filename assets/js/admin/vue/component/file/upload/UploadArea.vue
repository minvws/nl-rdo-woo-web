<script setup>
import Icon from '@admin-fe/component/Icon.vue';
import UploadedFile from '@admin-fe/component/file/UploadedFile.vue';
import {
  areFilesEqual,
  filterDataTransferFiles,
  formatFileSize,
  formatList,
  isValidMaxFileSize,
  MimeTypes,
  validateFiles,
} from '@js/admin/utils';
import { uniqueId } from '@utils';
import { useElementVisibility } from '@vueuse/core';
import {
  computed,
  inject,
  onBeforeUnmount,
  provide,
  useSlots,
  ref,
  watch,
} from 'vue';
import AlreadyUploadedFiles from './AlreadyUploadedFiles.vue';
import DangerousFiles from './DangerousFiles.vue';
import InvalidFiles from './InvalidFiles.vue';
import SelectedFiles from './SelectedFiles.vue';
import UploadVisual from './UploadVisual.vue';
import { UPLOAD_AREA_ENDPOINT } from './static';

const uploadAreaElement = ref(null);
const isUploadAreaVisible = useElementVisibility(uploadAreaElement);
const uploadAreaIdentifierClass = 'vue-upload-area';

const emit = defineEmits([
  'isUploading',
  'selected',
  'uploaded',
  'uploadError',
]);

const props = defineProps({
  allowedFileTypes: {
    type: Array,
    default: () => ['PDF'],
  },
  allowedMimeTypes: {
    type: Array,
    default: () => [...MimeTypes.Pdf],
  },
  allowMultiple: {
    type: Boolean,
    default: false,
  },
  enableAutoUpload: {
    type: Boolean,
    default: false,
  },
  endpoint: {
    type: String,
  },
  id: {
    type: String,
  },
  maxFileSize: {
    type: Number,
  },
  name: {
    type: String,
  },
  payload: {
    type: Object,
    default: () => ({}),
  },
  tip: {
    type: String,
  },
  uploadedFileInfo: {
    type: [Object, null],
    default: null,
  },
});

provide(UPLOAD_AREA_ENDPOINT, props.endpoint ?? inject(UPLOAD_AREA_ENDPOINT));

const slots = useSlots();

const createUploadedFileInfo = () => {
  if (!props.uploadedFileInfo) {
    return null;
  }

  return {
    name: props.uploadedFileInfo.name,
    size: props.uploadedFileInfo.size,
    type: props.uploadedFileInfo.type,
  };
};

const id = props.id || uniqueId('upload-area');
const idOfFileLimitationsElement = `${id}-file-limitations`;
const idOfTipElement = `${id}-tip`;
const idOfSelectFilesElement = `${id}-select-files`;
const hasMaxFileSize = isValidMaxFileSize(props.maxFileSize);
const hasAllowedMimeTypes = props.allowedMimeTypes.length > 0;
const hasFileLimitations = hasMaxFileSize || hasAllowedMimeTypes;
const formattedFileSize = formatFileSize(props.maxFileSize);
const formattedAllowedFileTypes = formatList(props.allowedFileTypes, 'of');
const formattedFileOrFiles = props.allowMultiple ? 'Bestanden' : 'Bestand';
const invalidSizeFiles = ref([]);
const invalidTypeFiles = ref([]);
const dangerousFiles = ref([]);
const selectedFiles = ref(new Map());
const uploadedFile = ref(createUploadedFileInfo());
const uploadedFiles = ref([]);
const alreadyUploadedFiles = ref([]);
const failedFiles = ref([]);
const isUploading = computed(() => {
  if (!props.enableAutoUpload) {
    return false;
  }

  return selectedFiles.value.size > 0;
});

const buttonAriaLabelledBy = [
  idOfSelectFilesElement,
  hasFileLimitations ? idOfFileLimitationsElement : undefined,
  props.tip ? idOfTipElement : undefined,
]
  .filter(Boolean)
  .join(' ');

const buttonElement = ref(null);
const inputElement = ref(null);
const uploadVisualElement = ref(null);

const abortController = new AbortController();

document.body.addEventListener(
  'dragenter',
  (event) => {
    event.stopPropagation();
    event.preventDefault();

    if (event.currentTarget.contains(event.relatedTarget)) {
      return;
    }

    if (!event.dataTransfer?.types.some((type) => type === 'Files')) {
      // The user is dragging something that isn't a file.
      return;
    }

    if (!isThisTheOnlyVisibleUploadArea()) {
      return;
    }

    uploadVisualElement.value?.coverWholePage(true);
    uploadVisualElement.value?.slideInUp();
  },
  { signal: abortController.signal },
);

const isThisTheOnlyVisibleUploadArea = () => {
  const uploadAreaElements = document.querySelectorAll(
    `.${uploadAreaIdentifierClass}`,
  );
  if (uploadAreaElements.length === 1) {
    return true;
  }

  const numberOfVisibleUploadAreas = [...uploadAreaElements].filter(
    (element) => element.dataset.isVisible === 'true',
  ).length;
  return numberOfVisibleUploadAreas === 1 && isUploadAreaVisible.value;
};

const onDragEnter = (event) => {
  if (event.currentTarget.contains(event.relatedTarget)) {
    return;
  }

  if (!event.dataTransfer?.types.some((type) => type === 'Files')) {
    // The user is dragging something that isn't a file.
    return;
  }

  if (isThisTheOnlyVisibleUploadArea()) {
    return;
  }

  uploadVisualElement.value?.coverWholePage(false);
  uploadVisualElement.value?.slideInUp();
};

const onDragLeave = (event) => {
  if (event.currentTarget.contains(event.relatedTarget)) {
    return;
  }

  uploadVisualElement.value?.slideOutDown();
};

const onDragOver = () => {
  // This function doesn't do a lot but it's necessary to make the drop event work.
};

const onFilesDropped = async (event) => {
  if (event.currentTarget.contains(event.relatedTarget)) {
    return;
  }

  uploadVisualElement.value?.slideOutUp();

  const dataTransfer = await filterDataTransferFiles(event.dataTransfer, false);
  addFiles(dataTransfer.files);
};

const selectFiles = () => {
  inputElement?.value.click();
};

const onFilesSelected = (event) => {
  addFiles(event.target.files);
};

const addFiles = (files) => {
  const { invalidSize, invalidType, valid } = validateFiles(
    [...files],
    props.allowedMimeTypes,
    props.maxFileSize,
  );
  invalidSizeFiles.value = [...invalidSize];
  invalidTypeFiles.value = [...invalidType];

  const limitedFiles = limitFiles(valid);

  [...limitedFiles].forEach((file) => {
    if (
      alreadyHasFile(file, [
        ...selectedFiles.value.values(),
        ...alreadyUploadedFiles.value,
      ])
    ) {
      return;
    }

    if (alreadyHasFile(file, [...uploadedFiles.value])) {
      alreadyUploadedFiles.value.push(file);
      return;
    }

    if (!props.allowMultiple) {
      selectedFiles.value.clear();
    }

    const id = uniqueId('file', 32);
    selectedFiles.value.set(id, file);
  });

  updateInputValue();
};

const limitFiles = (files) => {
  if (!props.allowMultiple && files.length > 1) {
    return files.slice(0, 1);
  }

  return files;
};

const alreadyHasFile = (file, files) =>
  files.some((currentFile) => areFilesEqual(currentFile, file));

const onDelete = (fileId) => {
  selectedFiles.value.delete(fileId);
  updateInputValue();

  buttonElement.value?.focus();
};

const onUploaded = (
  fileId,
  file,
  uploadId,
  uploadSuccessData,
  elementHasFocus,
) => {
  selectedFiles.value.delete(fileId);
  updateInputValue();

  if (elementHasFocus) {
    buttonElement.value?.focus();
  }

  uploadedFiles.value.push(file);
  uploadedFile.value = file;
  emit('uploaded', file, uploadId, uploadSuccessData);

  if (!props.allowMultiple) {
    dangerousFiles.value = [];
  }
};

const onUploadError = (fileId, file, error) => {
  selectedFiles.value.delete(fileId);
  updateInputValue();

  if (error.isUnsafeError) {
    dangerousFiles.value.push(file);
    emit('uploadError', fileId, file);
    return;
  }

  failedFiles.value.push(fileId);
  emit('uploadError', fileId, file);
};

const updateInputValue = () => {
  if (props.enableAutoUpload) {
    return;
  }

  const dataTransfer = new DataTransfer();
  [...selectedFiles.value.values()].forEach((file) =>
    dataTransfer.items.add(file),
  );
  inputElement.value.files = dataTransfer.files;

  emit('selected', inputElement.value.files);
};

const cleanup = () => {
  abortController.abort();
};

onBeforeUnmount(() => {
  cleanup();
});

watch(
  () => props.uploadedFileInfo,
  () => {
    uploadedFile.value = createUploadedFileInfo();
  },
);

watch(isUploading, (value) => emit('isUploading', value));
</script>

<template>
  <div>
    <div
      class="bhr-upload-area"
      :class="uploadAreaIdentifierClass"
      ref="uploadAreaElement"
      :data-is-visible="isUploadAreaVisible"
      @dragenter.stop.prevent="onDragEnter"
    >
      <button
        @click="selectFiles"
        :aria-labelledby="buttonAriaLabelledBy"
        class="bhr-upload-area__button"
        ref="buttonElement"
        type="button"
      >
        <span
          v-if="props.tip"
          :id="idOfTipElement"
          class="bhr-upload-area__tip"
          >{{ props.tip }}</span
        >

        <Icon name="to-top" />

        <span
          class="bhr-upload-area__select-files"
          :id="idOfSelectFilesElement"
        >
          <span class="font-bold text-bhr-sea-blue">
            {{ formattedFileOrFiles }} selecteren
          </span>
          of hierin slepen
        </span>

        <span
          v-if="hasFileLimitations"
          :id="idOfFileLimitationsElement"
          class="bhr-upload-area__file-limits"
        >
          <span v-if="hasAllowedMimeTypes">
            {{ formattedFileOrFiles }} van het type
            {{ formattedAllowedFileTypes }}
          </span>
          <span v-if="hasMaxFileSize">
            (max {{ formattedFileSize }} per bestand)
          </span>
        </span>
      </button>

      <input
        @change="onFilesSelected"
        :accept="
          hasAllowedMimeTypes ? props.allowedMimeTypes.join(',') : undefined
        "
        :id="id"
        :multiple="props.allowMultiple ? 'multiple' : undefined"
        :name="props.name"
        class="bhr-upload-area__input sr-only"
        ref="inputElement"
        tabindex="-1"
        type="file"
      />

      <SelectedFiles
        @select-files="selectFiles"
        @delete="onDelete"
        @uploaded="onUploaded"
        @uploadError="onUploadError"
        :allow-multiple="props.allowMultiple"
        :enable-auto-upload="props.enableAutoUpload"
        :files="selectedFiles"
        :payload="props.payload"
      />

      <div v-if="slots.default" class="text-left pb-4">
        <slot />
      </div>

      <output class="block text-left">
        <InvalidFiles
          :allowed-file-types="props.allowedFileTypes"
          :allowed-mime-types="props.allowedMimeTypes"
          :files="invalidTypeFiles"
        />

        <InvalidFiles
          :files="invalidSizeFiles"
          :max-file-size="props.maxFileSize"
        />

        <DangerousFiles
          :allow-multiple="props.allowMultiple"
          :files="dangerousFiles"
          class="mb-4"
        />

        <AlreadyUploadedFiles :files="alreadyUploadedFiles" class="mb-4" />
      </output>

      <div
        @dragleave.stop.prevent="onDragLeave"
        @dragover.stop.prevent="onDragOver"
        @drop.stop.prevent="onFilesDropped"
      >
        <UploadVisual ref="uploadVisualElement" />
      </div>
    </div>

    <div v-if="uploadedFile && !props.allowMultiple" class="pt-2">
      <UploadedFile
        :file-name="uploadedFile.name"
        :file-size="uploadedFile.size"
        :mimeType="uploadedFile.type"
      />
    </div>
  </div>
</template>
