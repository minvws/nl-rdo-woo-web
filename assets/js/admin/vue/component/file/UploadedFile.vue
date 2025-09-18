<script setup lang="ts">
import { formatFileSize } from '@js/admin/utils';
import { computed, useSlots } from 'vue';
import Icon from '../Icon.vue';
import MimeTypeIcon from './MimeTypeIcon.vue';

interface Props {
  canDelete: boolean;
  fileName: string;
  fileSize?: number;
  mimeType?: string;
  withdrawUrl?: string;
}

interface Emits {
  delete: [];
}

const props = withDefaults(defineProps<Props>(), {
  canDelete: false,
});

const emit = defineEmits<Emits>();
const slots = useSlots();

const onDelete = () => {
  emit('delete');
};

const defaultFileInfo = computed(() => {
  if (props.fileSize) {
    return formatFileSize(props.fileSize);
  }

  return '';
});
</script>

<template>
  <div class="bhr-file">
    <span class="bhr-file__left">
      <span class="bhr-file__icon-area">
        <MimeTypeIcon :mimeType="props.mimeType" :size="20" />
      </span>
      <span class="bhr-file__info-area">
        <span
          class="bhr-file__file-name group-hover:text-bhr-blue-800 group-focus:text-bhr-blue-800 break-all"
        >
          {{ props.fileName }}
        </span>
        <span class="bhr-file__file-info">
          <slot>{{ defaultFileInfo }}</slot>
        </span>
      </span>
    </span>

    <div v-if="slots.extra" class="flex">
      <slot name="extra"></slot>
    </div>

    <button
      @click="onDelete"
      class="bhr-btn-ghost-danger w-12"
      data-e2e-name="delete-file"
      type="button"
      v-if="props.canDelete"
    >
      <Icon color="fill-current" :size="20" name="trash-bin" />
      <span class="sr-only">Verwijder {{ props.fileName }}</span>
    </button>

    <a
      v-else-if="props.withdrawUrl"
      class="bhr-a px-4 content-center"
      data-e2e-name="withdraw-file"
      :href="props.withdrawUrl"
    >
      Intrekken<span class="sr-only"> ({{ props.fileName }})</span>
    </a>
  </div>
</template>
