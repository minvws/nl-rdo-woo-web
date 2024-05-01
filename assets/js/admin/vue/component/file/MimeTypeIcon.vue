<script setup>
  import Icon from '../Icon.vue';
  import { computed } from 'vue';
  import { getIconNameByMimeType } from '@js/admin/utils';

  const props = defineProps({
    mimeType: {
      type: String,
      required: false,
    },
    size: {
      type: Number,
      required: false,
    },
  });

  const name = computed(() => getIconNameByMimeType(props.mimeType));
</script>

<template>
  <Icon :name="name" :size="props.size" />
</template>
