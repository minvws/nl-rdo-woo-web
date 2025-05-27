<script setup lang="ts">
interface Props {
  isSecondary?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  isSecondary: false,
});

const buttonType = props.isSecondary ? 'button' : 'submit';
</script>

<template>
  <button
    :class="{
      'bhr-btn-filled-primary': !props.isSecondary,
      'bhr-btn-bordered-primary': props.isSecondary,
      'ml-2': props.isSecondary,
    }"
    :type="buttonType"
  >
    <slot />
  </button>
</template>
