<script setup>
import {
  formatFileSize,
  formatList,
  isValidMaxFileSize,
} from '@js/admin/utils';
import { computed } from 'vue';
import Alert from '../../Alert.vue';
import FilesList from './FilesList.vue';

const props = defineProps({
  allowedFileTypes: {
    type: Array,
    required: false,
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
const formattedAllowedFileTypes = formatList(props.allowedFileTypes, 'en');
const hasMaxFileSize = isValidMaxFileSize(props.maxFileSize);
const numberOfFiles = computed(() => props.files.length);
const firstFile = computed(() => props.files[0]);

const reason = hasMaxFileSize ? 'te groot' : 'van een ongeldig type';
</script>

<template>
  <div class="mb-4" v-if="numberOfFiles > 0">
    <Alert type="danger">
      <p>
        <template v-if="numberOfFiles === 1"
          >Het bestand "{{ firstFile.name }}" werd genegeerd omdat het
          {{ reason }}
          is{{
            hasMaxFileSize ? ` (${formatFileSize(firstFile.size)})` : ''
          }}.</template
        ><template v-else
          >De volgende bestanden werden genegeerd omdat ze
          {{ reason }} zijn.</template
        >
        <template v-if="hasAllowedMimeTypes">
          Alleen bestanden van het type
          {{ formattedAllowedFileTypes }} zijn toegestaan.
        </template>
        <template v-if="hasMaxFileSize">
          De maximale bestandsgrootte per bestand is
          {{ formatFileSize(props.maxFileSize) }}.
        </template>
      </p>

      <template v-if="numberOfFiles > 1" #extra>
        <FilesList :files="props.files" />
      </template>
    </Alert>
  </div>
</template>
