<script setup lang="ts">
import { formatFileSize, formatList } from '@js/admin/utils';
import {
  collectFileLimitLabels,
  collectFileLimitSizes,
  type FileUploadLimit,
} from '@js/admin/utils/file';

interface Props {
  fileOrFiles: string;
  id: string;
  limits: FileUploadLimit[];
}

const props = defineProps<Props>();
const maxFileSizes = collectFileLimitSizes(props.limits);

const formatAllowedFileLabels = () => {
  if (maxFileSizes.length < 2) {
    return formatList(collectFileLimitLabels(props.limits), 'of');
  }

  const labelsAndMaxFileSizes = maxFileSizes.map((maxFileSize) => {
    const labels = props.limits
      .filter((limit) => limit.size === maxFileSize)
      .map((limit) => limit.label);

    return `${labels.join(', ')} (max ${formatFileSize(maxFileSize)})`;
  });

  return formatList(labelsAndMaxFileSizes, 'of');
};

const formattedAllowedFileLabels = formatAllowedFileLabels();
</script>

<template>
  <span :id="props.id" class="bhr-upload-area__file-limits">
    <span v-if="formattedAllowedFileLabels.length > 0">
      {{ props.fileOrFiles }} van het type
      {{ formattedAllowedFileLabels }}
    </span>
    <span v-if="maxFileSizes.length === 1">
      (max {{ formatFileSize(maxFileSizes[0]) }} per bestand)
    </span>
  </span>
</template>
