<script setup lang="ts">
import { formatFileSize } from '@js/admin/utils';
import { computed } from 'vue';
import Alert from '../../Alert.vue';
import FilesList from './FilesList.vue';

interface Props {
  files: File[];
}

const props = withDefaults(defineProps<Props>(), {
  files: () => [],
});

const numberOfFiles = computed(() => props.files.length);
</script>

<template>
  <div v-if="numberOfFiles > 0">
    <Alert type="danger">
      <p v-if="numberOfFiles === 1">
        Het bestand "{{ props.files[0].name }}" ({{
          formatFileSize(props.files[0].size)
        }}) werd genegeerd omdat het al geüpload is.
      </p>
      <p v-else>
        De volgende bestanden werden genegeerd omdat ze al geüpload zijn.
      </p>

      <template v-if="numberOfFiles > 1" #extra>
        <FilesList :files="props.files" />
      </template>
    </Alert>
  </div>
</template>
