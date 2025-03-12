<script setup lang="ts">
import { getIconNameByMimeType } from '@js/admin/utils';
import { computed } from 'vue';
import Icon from '../Icon.vue';

interface Props {
  mimeType?: string;
  size?: number;
}

const props = defineProps<Props>();

const name = computed(() => getIconNameByMimeType(props.mimeType ?? ''));
</script>

<template>
  <Icon :name="name" :size="props.size" />
</template>
