<script setup>
import { computed } from 'vue';

const props = defineProps({
  isSecondary: {
    type: Boolean,
    required: false,
    default: false,
  },
});

const buttonType = computed(() => (props.isSecondary ? 'button' : 'submit'));
</script>

<template>
  <button
    class="bhr-button bhr-button--fixed-width"
    :class="{
      'bhr-button--primary': !props.isSecondary,
      'bhr-button--secondary': props.isSecondary,
      'ml-2': props.isSecondary,
    }"
    :type="buttonType"
  >
    <slot />
  </button>
</template>
