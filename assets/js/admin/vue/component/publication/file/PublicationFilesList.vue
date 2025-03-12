<script setup lang="ts">
import { computed } from 'vue';
import type { PublicationFileTypes, PublicationFile } from './interface';
import PublicationFileItem from './PublicationFileItem.vue';

interface Props {
  canDelete: boolean;
  endpoint: string;
  publicationFileTypes: PublicationFileTypes;
  files: Map<string, PublicationFile>;
}

interface Emits {
  deleted: [string];
  edit: [string];
}

const props = withDefaults(defineProps<Props>(), {
  files: () => new Map(),
  canDelete: false,
  publicationFileTypes: () => [],
});

const emit = defineEmits<Emits>();

const numberOfFiles = computed(() => props.files.size);
const hasFiles = computed(() => numberOfFiles.value > 0);
const hasMultipleFiles = computed(() => numberOfFiles.value > 1);

const createDeleteEndpoint = (id?: string) => `${props.endpoint}/${id}`;

const isFirstFile = (index: number) => index === 0;
const isLastFile = (index: number) => index === numberOfFiles.value - 1;

const onDeleted = (id: string) => {
  emit('deleted', id);
};

const onEdit = (id: string) => {
  emit('edit', id);
};
</script>

<template>
  <div v-if="hasFiles">
    <PublicationFileItem
      v-for="([, file], index) in [...files]"
      @deleted="onDeleted"
      @edit="onEdit"
      :can-delete="props.canDelete"
      :class="{
        '!rounded-b-none': hasMultipleFiles && isFirstFile(index),
        '!rounded-none':
          hasMultipleFiles && !isFirstFile(index) && !isLastFile(index),
        '!rounded-t-none': hasMultipleFiles && isLastFile(index),
        '-mt-px': hasMultipleFiles && !isFirstFile(index),
      }"
      :date="file.formalDate"
      :endpoint="createDeleteEndpoint(file.id)"
      :file-name="file.name"
      :file-size="file.size"
      :file-types="props.publicationFileTypes"
      :file-type-value="file.type"
      :id="file.id as string"
      :key="file.id"
      :mime-type="file.mimeType"
      :withdraw-url="file.withdrawUrl"
    />
  </div>
</template>
