<script setup>
  import Icon from './Icon.vue';

  const props = defineProps({
    size: {
      type: Number,
      required: false,
    },
  });
</script>

<template>
  <Icon name="loader" class="animate-spin" :size="props.size" />
</template>
