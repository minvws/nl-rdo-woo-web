<script setup>
  import Icon from '../../Icon.vue';
  import MimeTypeIcon from '../MimeTypeIcon.vue';
  import { computed, nextTick, onBeforeUnmount, ref } from 'vue';
  import { uploadFile } from '@js/admin/utils';
  import { collapseElement } from '@utils';

  const emit = defineEmits(['delete', 'uploaded', 'uploadError']);
  const props = defineProps({
    endpoint: {
      type: String,
    },
    file: {
      type: Object,
      required: true,
      default: () => ({}),
    },
    fileId: {
      type: String,
      required: true,
    },
    name: {
      type: String,
      required: false,
    },
  });

  const isAutoUploadEnabled = Boolean(props.endpoint);
  const isDeleteButtonVisible = ref(true);
  const isSpinnerVisible = ref(false);
  const isErrorVisible = ref(false);
  const isSuccessVisible = ref(false);
  const isProgressVisible = ref(isAutoUploadEnabled);
  const listElement = ref(null);

  const isIndicatorIconVisible = computed(() => [isSpinnerVisible, isErrorVisible, isSuccessVisible].some((reference) => reference.value));

  const progressId = `${props.fileId}-progress`;
  const progress = ref(0);

  let cleanupUpload;
  let uploadResultTimeoutId;
  const uploadResultTimeoutDuration = 1500;

  const triggerFileUpload = async () => {
    // Upload the file in the next tick to prevent an issue with icons not showing up
    await nextTick();

    cleanupUpload = uploadFile({
      endpoint: props.endpoint,
      file: props.file,
      // inputName: props.name,
      inputName: 'document_upload[upload]',
      onProgress: (fileProgress) => {
        progress.value = fileProgress;

        if (fileProgress === 100) {
          isDeleteButtonVisible.value = false;
          isSpinnerVisible.value = true;
        }
      },
      onSuccess: () => {
        isSpinnerVisible.value = false;
        isSuccessVisible.value = true;

        uploadResultTimeoutId = setTimeout(() => {
          collapseElement(listElement.value, true, () => {
            emit('uploaded', props.fileId, props.file);
          });
        }, uploadResultTimeoutDuration);
      },
      onError: () => {
        isSpinnerVisible.value = false;
        isErrorVisible.value = true;

        uploadResultTimeoutId = setTimeout(() => {
          collapseElement(listElement.value, true, () => {
            emit('uploadError', props.fileId, props.file);
          });
        }, uploadResultTimeoutDuration);
      }
    });
  }

  if (isAutoUploadEnabled) {
    triggerFileUpload();
  }

  const onDelete = () => {
    cleanup();

    if (isAutoUploadEnabled) {
      collapseElement(listElement.value, true, () => {
        emit('delete', props.fileId);
      });
      return;
    }

    emit('delete', props.fileId);
  };

  const cleanup = () => {
    if (cleanupUpload) {
      cleanupUpload();
    }

    if (uploadResultTimeoutId) {
      clearTimeout(uploadResultTimeoutId);
    }
  }

  onBeforeUnmount(() => {
    cleanup();
  });
</script>

<template>
  <li class="pb-1 duration-500" ref="listElement">
    <div class="flex">
      <div class="flex grow pl-4 py-1 truncate">
        <span class="mr-2"><MimeTypeIcon :mime-type="props.file.type" :size="20" /></span>
        <div class="leading-none pt-1.5 truncate">{{ props.file.name }}</div>
      </div>

      <div>
        <button
          v-if="isDeleteButtonVisible"
          @click="onDelete"
          class="cursor-pointer py-1 px-2 mr-2 hover-focus:text-bhr-maximum-red"
          type="button"
        >
          <Icon color="fill-current" name="trash-bin" size="16" /> <span class="sr-only">Verwijder {{ file.name }}</span>
        </button>

        <div v-if="isIndicatorIconVisible" class="py-1 px-2 mr-2" tabindex="-1">
          <Icon v-if="isSpinnerVisible" class="animate-spin" name="loader" size="16" />
          <Icon v-if="isErrorVisible" color="fill-bhr-maximum-red" name="cross-rounded-filled" size="16" />
          <Icon v-if="isSuccessVisible" color="fill-bhr-philippine-green" name="check-rounded-filled" size="16" />
        </div>
      </div>
    </div>

    <template v-if="isProgressVisible">
      <label class="sr-only" :for="progressId">Voortgang van {{ file.name }}</label>
      <div class="relative mx-4">
        <progress class="bhr-upload-area__progress" :id="progressId" max="100" :value="progress" />
      </div>
    </template>
  </li>
</template>
