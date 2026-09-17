<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

const element = ref<HTMLDivElement | null>(null);
const isCollapsed = defineModel({ default: false });
const style = reactive<{ height: string; overflow: string }>({
  height: '',
  overflow: '',
});

const emit = defineEmits<{
  collapsed: [];
}>();

const TRANSITION_DURATION_MS = 500;

let transitionTimeoutId: ReturnType<typeof setTimeout> | undefined;
let transitionId = 0;
let transitionCompleted = false;

const clearTransitionTimeout = () => {
  if (transitionTimeoutId === undefined) {
    return;
  }

  clearTimeout(transitionTimeoutId);
  transitionTimeoutId = undefined;
};

const startTransition = () => {
  clearTransitionTimeout();
  transitionId += 1;
  transitionCompleted = false;

  return transitionId;
};

const completeTransition = (id: number) => {
  if (id !== transitionId || transitionCompleted) {
    return;
  }

  transitionCompleted = true;
  clearTransitionTimeout();

  if (isCollapsed.value) {
    emit('collapsed');
    return;
  }

  style.height = '';
  style.overflow = '';
};

const scheduleTransitionCompletion = (id: number) => {
  transitionTimeoutId = setTimeout(() => {
    completeTransition(id);
  }, TRANSITION_DURATION_MS);
};

const collapse = () => {
  const id = startTransition();
  style.height = `${element.value?.scrollHeight}px`;
  style.overflow = 'hidden';

  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      if (id !== transitionId || transitionCompleted || !isCollapsed.value) {
        return;
      }

      style.height = '0px';
      scheduleTransitionCompletion(id);
    });
  });
};

const expand = () => {
  const id = startTransition();
  style.height = `${element.value?.scrollHeight}px`;
  style.overflow = 'hidden';

  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      if (id !== transitionId || transitionCompleted || isCollapsed.value) {
        return;
      }

      scheduleTransitionCompletion(id);
    });
  });
};

const onTransitionEnd = (event: TransitionEvent) => {
  if (event.target !== element.value) {
    return;
  }

  completeTransition(transitionId);
};

onMounted(() => {
  if (!isCollapsed.value) {
    return;
  }

  const id = startTransition();
  style.height = `${element.value?.scrollHeight}px`;
  style.overflow = 'hidden';
  style.height = '0px';
  completeTransition(id);
});

onBeforeUnmount(() => {
  transitionId += 1;
  transitionCompleted = true;
  clearTransitionTimeout();
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
