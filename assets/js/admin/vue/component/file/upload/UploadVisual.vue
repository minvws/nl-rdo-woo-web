<script setup lang="ts">
import { nextTick, onBeforeUnmount, reactive, ref } from 'vue';
import Icon from '../../Icon.vue';

let slideOutTimeoutId: ReturnType<typeof setTimeout>;

const TRANSITION_DURATION = 150;

const isVisible = ref(false);
const elementClassNames = reactive<{ animation: string[]; position: string }>({
  animation: [],
  position: 'absolute',
});
const dotElementClassNames = ref<string[]>([]);

const coverWholePage = (shouldCoverWholePage: boolean) => {
  elementClassNames.position = shouldCoverWholePage ? 'fixed' : 'absolute';
};

const slideInUp = async () => {
  isVisible.value = true;

  await nextTick();

  elementClassNames.animation = ['backdrop-blur-xs'];
  dotElementClassNames.value = ['bhr-upload-visual__dot--slide-in-up'];
};

const slideOut = async (direction: 'up' | 'down') => {
  elementClassNames.animation = ['delay-100', 'opacity-0'];
  dotElementClassNames.value = [
    direction === 'up'
      ? 'bhr-upload-visual__dot--slide-out-up'
      : 'bhr-upload-visual__dot--slide-out-down',
  ];

  slideOutTimeoutId = setTimeout(() => {
    isVisible.value = false;

    clearTimeout(slideOutTimeoutId);
  }, TRANSITION_DURATION + 50);
};

const slideOutDown = () => {
  slideOut('down');
};

const slideOutUp = () => {
  slideOut('up');
};

onBeforeUnmount(() => {
  clearTimeout(slideOutTimeoutId);
});

defineExpose({
  coverWholePage,
  slideInUp,
  slideOutDown,
  slideOutUp,
});
</script>

<template>
  <div
    class="bhr-upload-visual"
    :class="[...elementClassNames.animation, elementClassNames.position]"
    v-if="isVisible"
  >
    <div class="bhr-upload-visual__dot" :class="dotElementClassNames">
      <Icon color="fill-white" name="to-top" />
      <span class="block pt-2 font-bold">Uploaden</span>
    </div>
  </div>
</template>
