<script setup lang="ts">
import { computed } from 'vue';
import Alert from '../../Alert.vue';
import FilesList from './FilesList.vue';

interface Props {
  allowMultiple?: boolean;
  files?: File[];
}

const props = withDefaults(defineProps<Props>(), {
  files: () => [],
});

const numberOfFiles = computed(() => props.files.length);
</script>

<template>
  <div v-if="numberOfFiles > 0">
    <Alert type="warning">
      <p>
        <template v-if="props.allowMultiple"
          >Er zijn mogelijke gevaren gevonden in de onderstaande bestanden. Ze
          worden daarom niet opgeslagen. Probeer een ander bestand.</template
        >
        <template v-else
          >Er zijn mogelijk gevaren gevonden in het bestand, het bestand wordt
          niet opgeslagen. Probeer een ander bestand.</template
        >
      </p>

      <template v-if="props.allowMultiple" #extra>
        <FilesList :files="props.files" />
      </template>
    </Alert>
  </div>
</template>
