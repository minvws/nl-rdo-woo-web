<script setup lang="ts">
import Icon from './Icon.vue';

interface Props {
  size?: number;
}

const props = defineProps<Props>();
</script>

<template>
  <Icon name="loader" class="animate-spin" :size="props.size" />
</template>
