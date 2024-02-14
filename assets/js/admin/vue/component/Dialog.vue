<script setup>
  import { uniqueId } from '@js/utils';
  import Icon from './Icon.vue';
  import { ref, watch } from 'vue';

  const props = defineProps({
    title: { type: String, required: true },
  })

  const dialogElement = ref(null);
  const isOpen = defineModel({ default: false });
  const titleId = `${uniqueId('dialog')}-title`;

  const onClose = () => {
    isOpen.value = false;
  };

  const closeDialog = () => {
    dialogElement.value?.close();
  };

  const showDialog = () => {
    dialogElement.value?.showModal();
  };

  watch(isOpen, (value) => {
    if (value) {
      showDialog();
      return;
    }

    closeDialog();
  });
</script>

<template>
  <dialog
    @close="onClose"
    :aria-labelledby="titleId"
    class="bhr-dialog"
    ref="dialogElement"
  >
    <div class="bhr-dialog__content">
      <h1 class="bhr-dialog__title" :id="titleId">{{ props.title }}</h1>
      <slot />

      <form method="dialog">
        <button class="bhr-dialog__close-btn">
          <span class="sr-only">Sluit venster</span>
          <Icon name="cross" />
        </button>
      </form>
    </div>
  </dialog>
</template>
