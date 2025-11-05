<script setup lang="ts">
import { useSlots } from 'vue';
import Icon from './Icon.vue';

interface Props {
  type?: 'success' | 'info' | 'danger' | 'warning';
}

const props = withDefaults(defineProps<Props>(), {
  type: 'success',
});

const slots = useSlots();

const getAlertTypeClass = () => {
  switch (props.type) {
    case 'danger':
      return 'bhr-alert--danger';
    case 'info':
      return 'bhr-alert--info';
    case 'warning':
      return 'bhr-alert--warning';
    default:
      return 'bhr-alert--success';
  }
};

const getIconColor = () => {
  switch (props.type) {
    case 'danger':
      return 'stroke-bhr-red-700';
    case 'info':
      return 'stroke-bhr-blue-700';
    case 'warning':
      return 'stroke-bhr-yellow-800';
    default:
      return 'stroke-bhr-green-700';
  }
};

const getIconName = () => {
  switch (props.type) {
    case 'danger':
      return 'alert-circle';
    case 'info':
      return 'info-circle';
    case 'warning':
      return 'alert-triangle';
    default:
      return 'circle-check';
  }
};

const alertTypeClass = getAlertTypeClass();
const iconColor = getIconColor();
const iconName = getIconName();
</script>

<template>
  <div
    class="bhr-alert"
    :class="{ [alertTypeClass]: true }"
    data-e2e-name="alert"
  >
    <template v-if="slots.top">
      <slot name="top"></slot>
    </template>

    <div class="flex">
      <span class="mr-2">
        <Icon :color="iconColor" :name="iconName" :size="24" />
      </span>

      <div class="grow pt-0.5">
        <slot />
      </div>
    </div>

    <div v-if="slots.extra" class="mt-4 ml-8">
      <slot name="extra"></slot>
    </div>
  </div>
</template>
