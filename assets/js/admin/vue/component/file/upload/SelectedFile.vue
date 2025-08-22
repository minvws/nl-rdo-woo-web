<script setup lang="ts">
import {
  uploadFile,
  type OnUploadError,
  type UploadSuccessData,
} from '@js/admin/utils';
import { useFocusWithin } from '@vueuse/core';
import {
  computed,
  inject,
  nextTick,
  onBeforeUnmount,
  ref,
  useTemplateRef,
} from 'vue';
import Collapsible from '../../Collapsible.vue';
import Icon from '../../Icon.vue';
import MimeTypeIcon from '../MimeTypeIcon.vue';
import { UPLOAD_AREA_ENDPOINT } from './static';

interface Props {
  enableAutoUpload: boolean;
  file: File;
  fileId: string;
  payload?: Record<string, string>;
}

interface Emits {
  delete: [fileId: string];
  uploaded: [
    fileId: string,
    file: File,
    uploadId: string,
    uploadSuccessData: UploadSuccessData,
    hasFocus: boolean,
  ];
  uploading: [fileId: string, file: File];
  uploadError: [fileId: string, file: File, error: OnUploadError];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const endpoint = inject<string>(UPLOAD_AREA_ENDPOINT);

const deleteButtonElement = useTemplateRef<HTMLButtonElement>(
  'deleteButtonElement',
);
const resultDivElement = useTemplateRef<HTMLDivElement>('resultDivElement');
const wrapperElement = useTemplateRef<HTMLLIElement>('wrapperElement');

const isDeleteButtonVisible = ref(true);
const isCollapsed = ref(false);
const isSpinnerVisible = ref(false);
const isErrorVisible = ref(false);
const isSuccessVisible = ref(false);
const isProgressVisible = ref(props.enableAutoUpload);

const { focused: isFocusOnDeleteButton } = useFocusWithin(deleteButtonElement);
const { focused: isFocusWithinWrapper } = useFocusWithin(wrapperElement);

const isIndicatorIconVisible = computed(() =>
  [isSpinnerVisible, isErrorVisible, isSuccessVisible].some(
    (reference) => reference.value,
  ),
);

const progressId = `${props.fileId}-progress`;
const progress = ref(0);
const uploadResultTimeoutDuration = 1500;

let cleanupUpload: () => void;
let uploadSuccessData: UploadSuccessData;
let uploadId: string;
let uploadResultTimeoutId: ReturnType<typeof setTimeout>;

const triggerFileUpload = async () => {
  // Upload the file in the next tick to prevent an issue with icons not showing up
  await nextTick();

  emit('uploading', props.fileId, props.file);

  cleanupUpload = uploadFile({
    endpoint,
    file: props.file,
    payload: props.payload,
    onProgress: async (fileProgress) => {
      progress.value = fileProgress;

      if (fileProgress === 100) {
        const hasFocusOnDeleteButton = isFocusOnDeleteButton.value;

        isDeleteButtonVisible.value = false;
        isSpinnerVisible.value = true;

        await nextTick();

        if (hasFocusOnDeleteButton) {
          resultDivElement.value?.focus();
        }
      }
    },
    onSuccess: (uploadUuid: string, data: UploadSuccessData) => {
      uploadId = uploadUuid;
      uploadSuccessData = data;
      isSpinnerVisible.value = false;
      isSuccessVisible.value = true;

      uploadResultTimeoutId = setTimeout(() => {
        isCollapsed.value = true;
      }, uploadResultTimeoutDuration);
    },
    onError: (error: OnUploadError) => {
      isSpinnerVisible.value = false;
      isErrorVisible.value = true;

      uploadResultTimeoutId = setTimeout(() => {
        emit('uploadError', props.fileId, props.file, error);
      }, uploadResultTimeoutDuration);
    },
  });
};

if (props.enableAutoUpload) {
  triggerFileUpload();
}

const onDelete = () => {
  cleanup();
  emit('delete', props.fileId);
};

const onCollasped = () => {
  cleanup();

  emit(
    'uploaded',
    props.fileId,
    props.file,
    uploadId,
    uploadSuccessData,
    isFocusWithinWrapper.value,
  );
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
            <span class="mr-2"
              ><MimeTypeIcon :mime-type="props.file.type" :size="20"
            /></span>
            <div class="leading-none pt-1.5 truncate">
              {{ props.file.name }}
            </div>
          </div>

          <div>
            <button
              v-if="isDeleteButtonVisible"
              @click="onDelete"
              class="cursor-pointer py-1 px-2 mr-2 hover-focus:text-bhr-maximum-red"
              ref="deleteButtonElement"
              type="button"
            >
              <Icon color="fill-current" name="trash-bin" :size="16" />
              <span class="sr-only">Verwijder {{ file.name }}</span>
            </button>

            <div
              v-if="isIndicatorIconVisible"
              class="py-1 px-2 mr-2"
              ref="resultDivElement"
              tabindex="-1"
            >
              <Icon
                v-if="isSpinnerVisible"
                class="animate-spin"
                name="loader"
                :size="16"
              />
              <Icon
                v-if="isErrorVisible"
                color="fill-bhr-maximum-red"
                name="cross-rounded-filled"
                :size="16"
              />
              <Icon
                v-if="isSuccessVisible"
                color="fill-bhr-philippine-green"
                name="check-rounded-filled"
                :size="16"
              />
            </div>
          </div>
        </div>

        <template v-if="isProgressVisible">
          <label class="sr-only" :for="progressId"
            >Voortgang van {{ file.name }}</label
          >
          <div class="relative mx-4">
            <progress
              class="bhr-upload-area__progress"
              :id="progressId"
              max="100"
              :value="progress"
            />
          </div>
        </template>
      </div>
    </Collapsible>
  </li>
</template>
