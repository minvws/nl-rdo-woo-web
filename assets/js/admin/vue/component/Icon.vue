<script setup lang="ts">
import { computed } from 'vue';
import filePath from '@img/admin/icons.svg';

interface Props {
  color?: string;
  height?: number;
  name: string;
  size?: number;
  width?: number;
}

const props = withDefaults(defineProps<Props>(), {
  color: 'fill-bhr-dim-gray',
});

const getDimension = (dimension: 'height' | 'width') => {
  if (props.size) {
    return props.size;
  }
  return props[dimension] || 24;
};

const imagePath = computed(() => `${filePath}#${props.name}`);
const height = computed(() => getDimension('height'));
const width = computed(() => getDimension('width'));
</script>

<template>
  <svg
    aria-hidden="true"
    class="bhr-icon"
    :class="color"
    :height="height"
    :width="width"
  >
    <use :xlink:href="imagePath"></use>
  </svg>
</template>
