<script setup>
  import Alert from '../../Alert.vue';
  import { formatFileSize, formatList, getExtenstionsByMimeTypes } from '@js/admin/utils';

  const props = defineProps({
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

  const formattedValidExtensions = formatList(getExtenstionsByMimeTypes(props.allowedMimeTypes), 'en');
  const hasMaFileSize = Number.isInteger(props.maxFileSize) && props.maxFileSize > 0;
  const hasAllowedMimeTypes = props.allowedMimeTypes.length > 0;
</script>

<template>
  <div v-if="files.length > 0">
    <Alert type="danger">
      <p>
        De volgende bestanden werden genegeerd.
        <template v-if="hasAllowedMimeTypes">Alleen bestanden van het type {{ formattedValidExtensions }} zijn toegestaan.</template> <template v-if="hasMaFileSize">De maximale bestandsgrootte per bestand is {{ formatFileSize(props.maxFileSize) }}.</template>
      </p>

      <template #extra>
        <ul class="bhr-ul grid grid-cols-2 gap-x-8 gap-y-1">
          <li class="bhr-li" v-for="entry in files" :key="entry.file.name">
            <div class="truncate">{{ entry.file.name }} ({{ formatFileSize(entry.file.size) }})</div>
          </li>
        </ul>
      </template>
    </Alert>
  </div>
</template>
