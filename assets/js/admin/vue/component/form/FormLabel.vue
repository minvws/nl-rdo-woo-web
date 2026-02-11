<script setup lang="ts">
interface Props {
  for: string;
  required?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  required: true,
});
</script>

<template>
  <label class="bhr-label" :for="props.for">
    <slot />
    <span v-if="!props.required" class="bhr-optional ml-1">
      <span class="sr-only"> (</span>optioneel<span class="sr-only"
        >)</span
      ></span
    >
  </label>
</template>
