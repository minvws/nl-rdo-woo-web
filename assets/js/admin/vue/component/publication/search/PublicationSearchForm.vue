<script setup lang="ts">
import { onKeyDown } from '@utils';
import { useFocusWithin } from '@vueuse/core';
import { onBeforeUnmount, ref, watch } from 'vue';

let abortController: AbortController;

const emit = defineEmits<{
  escape: [];
  focusOut: [];
}>();

const formElement = ref<HTMLFormElement>();

const observe = () => {
  unObserve();

  abortController = new AbortController();

  onKeyDown(
    'Escape',
    () => {
      emit('escape');
    },
    { signal: abortController.signal },
  );
};

const unObserve = () => {
  abortController?.abort();
};

onBeforeUnmount(() => {
  unObserve();
});

const { focused } = useFocusWithin(formElement);

let checkFocusTimeout: globalThis.Timeout;

watch(focused, (value) => {
  clearTimeout(checkFocusTimeout);

  if (value) {
    observe();
    return;
  }

  checkFocusTimeout = setTimeout(() => {
    unObserve();
    emit('focusOut');
  }, 100);
});
</script>

<template>
  <form
    @submit.prevent="() => {}"
    class="relative"
    method="get"
    ref="formElement"
  >
    <slot />
  </form>
</template>
