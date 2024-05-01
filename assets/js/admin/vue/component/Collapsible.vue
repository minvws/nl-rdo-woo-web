<script setup>
  import { reactive, defineModel, nextTick, ref, watch } from 'vue';

  const classNames = ref([]);
  const element = ref(null);
  const isCollapsed = defineModel({ default: false });
  const style = reactive({});

  const emit = defineEmits('collapsed');

  const onTransitionEnd = () => {
    if (isCollapsed.value) {
      emit('collapsed');
      return;
    }

    style.height = null;
    style.overflow = null;
  };

  const collapse = async () => {
    style.height = `${element.value.scrollHeight}px`;
    style.overflow = `hidden`;

    setTimeout(() => {
      style.height = `0px`;
    }, 100);
  };

  const expand = () => {
    style.height = `${element.value.scrollHeight}px`;
    style.overflow = `hidden`;
  };

  watch(
    isCollapsed,
    (shouldCollapse) => {
      if (shouldCollapse) {
        collapse();
        return;
      }

      expand();
    }
  );
</script>

<template>
  <div
    class="transition-[height] duration-500"
    ref="element"
    :style="style"
    @transitionend="onTransitionEnd"
  >
    <slot />
  </div>
</template>
