<script setup>
  import Icon from '../../Icon.vue';
  import InvalidFiles from './InvalidFiles.vue';
  import SelectedFiles from './SelectedFiles.vue';
  import UploadedFile from '../UploadedFile.vue';
  import UploadVisual from './UploadVisual.vue';
  import { areFilesEqual, filterDataTransferFiles, formatFileSize, formatList, getExtenstionsByMimeTypes, validateFiles } from '@js/admin/utils';
  import { formatNumber, uniqueId } from '@utils';
  import { ref, watch } from 'vue';

  const emit = defineEmits(['selected']);

  const props = defineProps({
    allowedMimeTypes: {
      type: Array,
      required: false,
      default: () => [],
    },
    allowMultiple: {
      type: Boolean,
      required: false,
      default: false,
    },
    endpoint: {
      type: String,
      required: false,
    },
    id: {
      type: String,
      required: false,
    },
    maxFileSize: {
      type: Number,
      required: false,
    },
    name: {
      type: String,
      required: false,
    },
    tip: {
      type: String,
      required: false,
    },
  });

  const allowedMimeTypes = [...new Set(props.allowedMimeTypes).values()];
  const id = props.id || uniqueId('upload-area');
  const idOfFileLimitationsElement = `${id}-file-limitations`;
  const idOfTipElement = `${id}-tip`;
  const idOfSelectFilesElement = `${id}-select-files`;
  const hasMaxFileSize = props.maxFileSize !== undefined;
  const hasAllowedMimeTypes = allowedMimeTypes.length > 0;
  const hasFileLimitations = hasMaxFileSize || hasAllowedMimeTypes;
  const formattedFileSize = formatFileSize(props.maxFileSize || 0);
  const formattedValidExtensions = formatList(getExtenstionsByMimeTypes(allowedMimeTypes), 'of');
  const formattedFileOrFiles = props.allowMultiple ? 'Bestanden' : 'Bestand';
  const invalidFiles = ref([]);
  const selectedFiles = ref(new Map());
  const uploadedFile = ref(null);
  const uploadedFiles = ref([]);
  const failedFiles = ref([]);

  const buttonAriaLabelledBy = [
    idOfSelectFilesElement,
    hasFileLimitations ? idOfFileLimitationsElement : undefined,
    props.tip ? idOfTipElement : undefined,
  ].filter(Boolean).join(' ');

  const buttonElement = ref(null);
  const inputElement = ref(null);
  const uploadVisualElement = ref(null);

  const onDragEnter = (event) => {
    if ((event.currentTarget).contains(event.relatedTarget)) {
      return;
    }

    uploadVisualElement.value?.slideInUp();
  };

  const onDragLeave = (event) => {
    if ((event.currentTarget).contains(event.relatedTarget)) {
      return;
    }

    uploadVisualElement.value?.slideOutDown();
  };

  const onDragOver = () => {
    // This function doesn't do a lot but it's necessary to make the drop event work.
  };

  const onFilesDropped = async (event) => {
    if ((event.currentTarget).contains(event.relatedTarget)) {
      return;
    }

    uploadVisualElement.value?.slideOutUp();

    const dataTransfer = await filterDataTransferFiles(event.dataTransfer, false);
    addFiles(dataTransfer.files);
  }

  const selectFiles = () => {
    inputElement?.value.click();
  };

  const onFilesSelected = (event) => {
    addFiles(event.target.files);
  };

  const addFiles = (files) => {
    const { invalidFiles: invalid, validFiles } = validateFiles(files, allowedMimeTypes, props.maxFileSize);
    invalidFiles.value = [...invalid.values()];

    const limitedFiles = limitFiles(validFiles);

    [...limitedFiles].forEach((file) => {
      if (alreadyHasFile(file, [...selectedFiles.value.values()])) {
        return;
      }

      const id = uniqueId('file', 32);
      selectedFiles.value.set(id, file);
      updateInputValue();
    });
  };

  const limitFiles = (files) => {
    if (!props.allowMultiple && files.length > 1) {
      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(files.item(0));
      return dataTransfer.files;
    }

    return files;
  };

  const alreadyHasFile = (file, files) => files.some((currentFile) => areFilesEqual(currentFile, file));

  const onDelete = (fileId) => {
    selectedFiles.value.delete(fileId);
    updateInputValue();

    buttonElement.value?.focus();
  };

  const onUploaded = (fileId, file) => {
    selectedFiles.value.delete(fileId);
    updateInputValue();

    uploadedFile.value = file;
    uploadedFiles.value.push(file);
  };

  const onUploadError = (fileId) => {
    selectedFiles.value.delete(fileId);
    updateInputValue();

    failedFiles.value.push(fileId);
  };

  const updateInputValue = () => {
    const dataTransfer = new DataTransfer();
    [...selectedFiles.value.values()].forEach((file) => dataTransfer.items.add(file));
    inputElement.value.files = dataTransfer.files;
  };
</script>

<template>
  <div>
    <div role="status">
      <InvalidFiles
        :allowed-mime-types="allowedMimeTypes"
        :files="invalidFiles"
        :max-file-size="props.maxFileSize"
        class="mb-4"
      />
    </div>

    <div class="bhr-upload-area" @dragenter.stop.prevent="onDragEnter">
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
        >{{ props.tip }}</span>

        <Icon name="to-top" />

        <span class="bhr-upload-area__select-files" :id="idOfSelectFilesElement">
          <span
            class="font-bold text-bhr-sea-blue"
          >
            {{ formattedFileOrFiles }} selecteren
          </span> of hierin slepen
        </span>

        <span
          v-if="hasFileLimitations"
          :id="idOfFileLimitationsElement"
          class="bhr-upload-area__file-limits"
        >
          <span v-if="hasAllowedMimeTypes">
            {{ formattedFileOrFiles }} van het type {{ formattedValidExtensions }}
          </span>
          <span v-if="hasMaxFileSize">
            (max {{ formattedFileSize }} per bestand)
          </span>
        </span>
      </button>

      <input
        @change="onFilesSelected"
        :accept="hasAllowedMimeTypes ? allowedMimeTypes.join(',') : undefined"
        :id="id"
        :multiple="props.allowMultiple ? 'multiple' : undefined"
        :name="props.name"
        class="bhr-upload-area__input sr-only"
        ref="inputElement"
        tabindex="-1"
        type="file"
      >

      <SelectedFiles
        @select-files="selectFiles"
        @delete="onDelete"
        @uploaded="onUploaded"
        @uploadError="onUploadError"
        :allow-multiple="props.allowMultiple"
        :endpoint="props.endpoint"
        :files="selectedFiles"
        :name="props.name"
      />

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
