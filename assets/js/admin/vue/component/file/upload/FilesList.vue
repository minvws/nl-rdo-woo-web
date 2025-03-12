<script setup lang="ts">
import { formatFileSize } from '@js/admin/utils';

interface Props {
  files: File[];
}

const props = withDefaults(defineProps<Props>(), {
  files: () => [],
});
</script>

<template>
  <ul
    v-if="props.files.length > 0"
    class="bhr-ul grid grid-cols-2 gap-x-8 gap-y-1"
  >
    <li class="bhr-li" v-for="file in props.files" :key="file.name">
      <div class="truncate">
        {{ file.name }} ({{ formatFileSize(file.size) }})
      </div>
    </li>
  </ul>
</template>
