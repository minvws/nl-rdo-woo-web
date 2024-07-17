<script setup>
  import { formatExtensions, formatFileSize, isValidMaxFileSize } from '@js/admin/utils';
  import { computed } from 'vue';
  import Alert from '../../Alert.vue';
  import FilesList from './FilesList.vue';

  const props = defineProps({
    allowedExtensions: {
      type: Array,
      required: true,
      default: () => [],
    },
    allowedMimeTypes: {
      type: Array,
      required: false,
      default: () => [],
    },
    files: {
      type: Array,
      default: () => [],
    },
    maxFileSize: {
      type: Number,
      required: false,
    },
  });

  const hasAllowedMimeTypes = props.allowedMimeTypes.length > 0;
  const formattedValidExtensions = hasAllowedMimeTypes ? formatExtensions(props.allowedExtensions, 'en') : '';
  const hasMaxFileSize = isValidMaxFileSize(props.maxFileSize);
  const numberOfFiles = computed(() => props.files.length);
  const firstFile = computed(() => props.files[0].file);
</script>

<template>
  <div v-if="numberOfFiles > 0">
    <Alert type="danger">
      <p>
        <template v-if="numberOfFiles === 1">Het bestand "{{ firstFile.name }}" ({{ formatFileSize(firstFile.size) }}) werd genegeerd omdat het invalide is.</template><template v-else>De volgende bestanden werden genegeerd omdat ze invalide zijn.</template>
        <template v-if="hasAllowedMimeTypes"> Alleen bestanden van het type {{ formattedValidExtensions }} zijn toegestaan.</template> <template v-if="hasMaxFileSize">De maximale bestandsgrootte per bestand is {{ formatFileSize(props.maxFileSize) }}.</template>
      </p>

      <template v-if="numberOfFiles > 1" #extra>
        <FilesList :files="props.files.map((invalidFile) => invalidFile.file)" />
      </template>
    </Alert>
  </div>
</template>
