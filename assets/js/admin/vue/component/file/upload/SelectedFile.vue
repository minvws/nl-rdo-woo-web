<script setup>
  import Collapsible from '../../Collapsible.vue';
  import Icon from '../../Icon.vue';
  import MimeTypeIcon from '../MimeTypeIcon.vue';
  import { computed, inject, nextTick, onBeforeUnmount, ref } from 'vue';
  import { uploadFile } from '@js/admin/utils';

  const emit = defineEmits(['delete', 'uploaded', 'uploading', 'uploadError']);
  const props = defineProps({
    enableAutoUpload: {
      type: Boolean,
      default: false,
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
  });

  const deleteButtonElement = ref(null);
  const groupId = inject('groupId');
  const isDeleteButtonVisible = ref(true);
  const isCollapsed = ref(false);
  const isSpinnerVisible = ref(false);
  const isErrorVisible = ref(false);
  const isSuccessVisible = ref(false);
  const isProgressVisible = ref(props.enableAutoUpload);
  const resultDivElement = ref(null);
  const wrapperElement = ref(null);

  const isIndicatorIconVisible = computed(() => [isSpinnerVisible, isErrorVisible, isSuccessVisible].some((reference) => reference.value));

  const progressId = `${props.fileId}-progress`;
  const progress = ref(0);

  let cleanupUpload;
  let uploadId;
  let uploadResultTimeoutId;
  const uploadResultTimeoutDuration = 1500;

  const triggerFileUpload = async () => {
    // Upload the file in the next tick to prevent an issue with icons not showing up
    await nextTick();

    emit('uploading', props.fileId, props.file)

    cleanupUpload = uploadFile({
      file: props.file,
      groupId,
      onProgress: async (fileProgress) => {
        progress.value = fileProgress;

        if (fileProgress === 100) {
          const isFocusOnDeleteButton = document.activeElement === deleteButtonElement.value;

          isDeleteButtonVisible.value = false;
          isSpinnerVisible.value = true;

          await nextTick();

          if (isFocusOnDeleteButton) {
            resultDivElement.value.focus();
          }
        }
      },
      onSuccess: (uploadUuid) => {
        uploadId = uploadUuid;
        isSpinnerVisible.value = false;
        isSuccessVisible.value = true;

        uploadResultTimeoutId = setTimeout(() => {
          isCollapsed.value = true;
        }, uploadResultTimeoutDuration);
      },
      onError: (error) => {
        isSpinnerVisible.value = false;
        isErrorVisible.value = true;

        uploadResultTimeoutId = setTimeout(() => {
          emit('uploadError', props.fileId, props.file, error);
        }, uploadResultTimeoutDuration);
      }
    });
  }

  if (props.enableAutoUpload) {
    triggerFileUpload();
  }

  const onDelete = () => {
    cleanup();
    emit('delete', props.fileId);
  };

  const onCollasped = () => {
    cleanup();

    emit('uploaded', props.fileId, props.file, uploadId, wrapperElement.value.contains(document.activeElement));
  };

  const cleanup = () => {
    if (cleanupUpload) {
      cleanupUpload();
    }

    if (uploadResultTimeoutId) {
      clearTimeout(uploadResultTimeoutId);
    }
  };

  onBeforeUnmount(() => {
    cleanup();
  });
</script>

<template>
  <li ref="wrapperElement">
    <Collapsible v-model="isCollapsed" @collapsed="onCollasped">
      <div class="pb-1">
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
              ref="deleteButtonElement"
              type="button"
            >
              <Icon color="fill-current" name="trash-bin" size="16" /> <span class="sr-only">Verwijder {{ file.name }}</span>
            </button>

            <div v-if="isIndicatorIconVisible" class="py-1 px-2 mr-2" ref="resultDivElement" tabindex="-1">
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
      </div>
    </Collapsible>
  </li>
</template>
