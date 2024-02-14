<script setup>
  import Icon from '../../Icon.vue';
  import { onBeforeUnmount, ref } from 'vue';

  let slideInTimeoutId;
  let slideOutTimeoutId;

  const isHidden = ref(true);

  const slideInUp = () => {
    isHidden.value = false;
  };

  const slideOut = (direction) => {
    isHidden.value = true;
  };

  const slideOutDown = () => {
    slideOut('down');
  };

  const slideOutUp = () => {
    slideOut('up');
  };

  onBeforeUnmount(() => {
    clearTimeout(slideInTimeoutId);
    clearTimeout(slideOutTimeoutId);
  });

  defineExpose({
    slideInUp,
    slideOutDown,
    slideOutUp,
  });
</script>

<template>
  <div
    class="bhr-upload-visual"
    :class="{ hidden: isHidden }"
  >
    <div class="bhr-upload-visual__dot">
      <Icon color="fill-white" name="to-top" />
      <span class="block pt-2 font-bold">Uploaden</span>
    </div>
  </div>
</template>
