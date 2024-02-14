<script setup>
  import SkipLink from '../../SkipLink.vue';
  import SelectedFile from './SelectedFile.vue';
  import { uniqueId } from '@js/utils';
  import { computed } from 'vue';

  const emit = defineEmits(['delete', 'selectFiles', 'uploaded', 'uploadError']);
  const props = defineProps({
    allowMultiple: {
      type: Boolean,
    },
    endpoint: {
      type: String,
      required: false,
    },
    files: {
      type: Map,
      required: true,
      default: () => new Map(),
    },
    name: {
      type: String,
    },
  });

  const formattedFileOrFiles = props.allowMultiple ? 'bestanden' : 'bestand';
  const numberFiles = computed(() => props.files.size);
  const hasFiles = computed(() => numberFiles.value > 0);
  const id = uniqueId('upload-area-files');
  const idOfAboveSkipLink = `${id}-above-list`;
  const idOfBelowSkipLink = `${id}-below-list`;

  const onDelete = (fileId) => {
    emit('delete', fileId);
  };

  const onUploaded = (fileId, file) => {
    emit('uploaded', fileId, file);
  };

  const onUploadError = (fileId, file) => {
    emit('uploadError', fileId, file);
  };

  const onSelectFiles = () => {
    emit('selectFiles');
  };
</script>

<template>
  <div class="bhr-upload-area__files-area">
    <template v-if="hasFiles">
      <SkipLink class="focus:mt-2" :href="idOfBelowSkipLink" :id="idOfAboveSkipLink">
        Naar einde van lijst met te uploaden bestanden
      </SkipLink>

      <h3 class="sr-only">Te uploaden bestanden</h3>

      <ul class="bhr-upload-area__files-list" :class="{ 'grid grid-cols-3': numberFiles >= 3, 'grid grid-cols-2': numberFiles === 2 }">
        <SelectedFile
          @delete="onDelete"
          @uploaded="onUploaded"
          @upload-error="onUploadError"
          v-for="[fileId, file] in props.files"
          :endpoint="props.endpoint"
          :file="file"
          :file-id="fileId"
          :key="fileId"
          :name="props.name"
        />
      </ul>

      <SkipLink class="focus:mb-2" :href="idOfAboveSkipLink" :id="idOfBelowSkipLink">
        Naar begin van lijst met te uploaden bestanden
      </SkipLink>
    </template>

    <div @click="onSelectFiles" v-else class="bhr-upload-area__no-files">
      Geen {{ formattedFileOrFiles }} geselecteerd
    </div>
  </div>
</template>
