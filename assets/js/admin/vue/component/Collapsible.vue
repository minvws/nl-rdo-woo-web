<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';

const element = ref<HTMLDivElement | null>(null);
const isCollapsed = defineModel({ default: false });
const style = reactive<{ height: string; overflow: string }>({
  height: '',
  overflow: '',
});

const emit = defineEmits<{
  collapsed: [];
}>();

const onTransitionEnd = () => {
  if (isCollapsed.value) {
    emit('collapsed');
    return;
  }

  style.height = '';
  style.overflow = '';
};

const collapse = async () => {
  style.height = `${element.value?.scrollHeight}px`;
  style.overflow = 'hidden';

  setTimeout(() => {
    style.height = '0px';
  }, 100);
};

const expand = () => {
  style.height = `${element.value?.scrollHeight}px`;
  style.overflow = 'hidden';
};

onMounted(async () => {
  if (isCollapsed.value) {
    await collapse();
    onTransitionEnd();
  }
});

watch(isCollapsed, (shouldCollapse) => {
  if (shouldCollapse) {
    collapse();
    return;
  }

  expand();
});
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
