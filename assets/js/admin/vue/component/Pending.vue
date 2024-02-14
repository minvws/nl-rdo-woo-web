<script setup>
  import Spinner from './Spinner.vue';

  const props = defineProps({
    isPending: {
      type: Boolean,
      required: false,
      default: false,
    },
  });
</script>

<template>
  <div class="relative">
    <div class="transition-all" :class="{ 'blur-sm': props.isPending }">
      <slot />
    </div>

    <Spinner v-if="props.isPending" class="absolute inset-1/2 -mt-6 -ml-6" :size="48" />
  </div>
</template>
