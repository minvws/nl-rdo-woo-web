<script setup>
  import Alert from '../../Alert.vue';
  import { formatFileSize, formatList, getExtenstionsByMimeTypes, isValidMaxFileSize } from '@js/admin/utils';
  import { computed } from 'vue';

  const props = defineProps({
    files: {
      type: Array,
      default: () => [],
    },
  });

  const numberOfFiles = computed(() => props.files.length);
</script>

<template>
  <div v-if="numberOfFiles > 0">
    <Alert type="danger">
      <p v-if="numberOfFiles === 1">Het bestand "{{ props.files[0].name }}" ({{ formatFileSize(props.files[0].size) }}) werd genegeerd omdat het al geüpload is.</p>
      <p v-else>De volgende bestanden werden genegeerd omdat ze al geüpload zijn.</p>

      <template v-if="numberOfFiles > 1" #extra>
        <ul class="bhr-ul grid grid-cols-2 gap-x-8 gap-y-1">
          <li class="bhr-li" v-for="file in props.files" :key="file.name">
            <div class="truncate">{{ file.name }} ({{ formatFileSize(file.size) }})</div>
          </li>
        </ul>
      </template>
    </Alert>
  </div>
</template>
