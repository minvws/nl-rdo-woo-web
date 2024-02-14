<script setup>
  const props = defineProps({
    for: {
      type: String,
      required: true
    }
  });
</script>

<template>
  <label class="bhr-label" :for="props.for"><slot /></label>
</template>
