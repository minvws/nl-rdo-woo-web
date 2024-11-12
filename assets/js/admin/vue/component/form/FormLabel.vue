<script setup>
const props = defineProps({
  for: {
    type: String,
    required: true,
  },
  required: {
    type: Boolean,
    default: true,
    required: false,
  },
});
</script>

<template>
  <label class="bhr-label" :for="props.for"
    ><slot /><span v-if="!props.required" class="font-normal">
      (optioneel)</span
    ></label
  >
</template>
