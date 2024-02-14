<script setup>
import { computed } from 'vue';
import filePath from '@img/admin/icons.svg';

const props = defineProps({
  color: {
    type: String,
    required: false,
    default: 'fill-bhr-dim-gray',
  },
  height: {
    type: Number,
    required: false,
  },
  name: {
    type: String,
    required: true,
  },
  size: {
    type: Number,
    required: false,
  },
  width: {
    type: Number,
    required: false,
  },
});

const getDimension = (dimension) => {
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
