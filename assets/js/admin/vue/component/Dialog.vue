<script setup lang="ts">
import { uniqueId } from '@js/utils';
import Icon from './Icon.vue';
import { ref, watch } from 'vue';

interface Props {
  e2eName?: string;
  title: string;
}

interface Emits {
  close: [];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const dialogElement = ref<HTMLDialogElement | null>(null);
const isOpen = defineModel<boolean>({ default: false });
const titleId = `${uniqueId('dialog')}-title`;

const onClose = () => {
  isOpen.value = false;
};

const closeDialog = () => {
  dialogElement.value?.close();
  emit('close');
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
    :data-e2e-name="e2eName"
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
