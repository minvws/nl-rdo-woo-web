<script setup lang="ts">
import {
  collectFileLimitLabels,
  collectFileLimitSizes,
  formatFileSize,
  formatList,
  type FileUploadLimit,
} from '@js/admin/utils';
import { computed } from 'vue';
import Alert from '../../Alert.vue';
import FilesList from './FilesList.vue';

interface Props {
  files?: File[];
  haveInvalidSize: boolean;
  limits?: FileUploadLimit[];
}

const props = withDefaults(defineProps<Props>(), {
  files: () => [],
  limits: () => [],
});

const numberOfFiles = computed(() => props.files.length);
const firstFile = computed(() => props.files[0]);

const formatMaxFileSizes = () => {
  const fileSizes = collectFileLimitSizes(props.limits);
  if (fileSizes.length === 1) {
    return formatFileSize(fileSizes[0]);
  }

  const fileSizesWithLabels = fileSizes.map((fileSize) => {
    const labels = props.limits
      .filter((limit) => limit.size === fileSize)
      .map((limit) => limit.label);

    return `${formatFileSize(fileSize)} (${labels.join(', ')})`;
  });

  return formatList(fileSizesWithLabels, 'of');
};

const formattedAllowedFileTypes = formatList(
  collectFileLimitLabels(props.limits),
  'en',
);
const formattedMaxFileSizes = formatMaxFileSizes();
const reason = props.haveInvalidSize ? 'te groot' : 'van een ongeldig type';
</script>

<template>
  <div class="mb-4" v-if="numberOfFiles > 0">
    <Alert type="danger">
      <p>
        <template v-if="numberOfFiles === 1"
          >Het bestand "{{ firstFile.name }}" werd genegeerd omdat het
          {{ reason }}
          is{{
            props.haveInvalidSize ? ` (${formatFileSize(firstFile.size)})` : ''
          }}.</template
        ><template v-else
          >De volgende bestanden werden genegeerd omdat ze
          {{ reason }} zijn.</template
        >
        <template v-if="props.haveInvalidSize">
          De maximale bestandsgrootte per bestand is
          {{ formattedMaxFileSizes }}.
        </template>
        <template v-else>
          Alleen bestanden van het type
          {{ formattedAllowedFileTypes }} zijn toegestaan.
        </template>
      </p>

      <template v-if="numberOfFiles > 1" #extra>
        <FilesList :files="props.files" />
      </template>
    </Alert>
  </div>
</template>
