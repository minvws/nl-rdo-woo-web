<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  color?: string;
  height?: number;
  name: string;
  size?: number;
  width?: number;
}

const props = withDefaults(defineProps<Props>(), {
  color: 'fill-bhr-gray-700',
});

const getDimension = (dimension: 'height' | 'width') => {
  if (props.size) {
    return props.size;
  }
  return props[dimension] || 24;
};

const imagePath = computed(
  () => `/img/admin/icons.svg?${__GIT_HASH__}#${props.name}`,
);
const heightComputed = computed(() => getDimension('height'));
const widthComputed = computed(() => getDimension('width'));
</script>

<template>
  <svg
    aria-hidden="true"
    class="bhr-icon"
    :class="color"
    :height="heightComputed"
    :width="widthComputed"
  >
    <use :xlink:href="imagePath"></use>
  </svg>
</template>
