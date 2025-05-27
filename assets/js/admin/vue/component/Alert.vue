<script setup lang="ts">
import { useSlots } from 'vue';
import Icon from './Icon.vue';

interface Props {
  type?: 'success' | 'info' | 'danger';
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
    default:
      return 'bhr-alert--success';
  }
};

const getIconColor = () => {
  switch (props.type) {
    case 'danger':
      return 'fill-current';
    case 'info':
      return 'fill-bhr-blue-800';
    default:
      return 'fill-bhr-philippine-green';
  }
};

const getIconName = () => {
  switch (props.type) {
    case 'danger':
      return 'exclamation-filled-colored';
    case 'info':
      return 'info-rounded-filled';
    default:
      return 'check-rounded-filled';
  }
};

const alertTypeClass = getAlertTypeClass();
const iconColor = getIconColor();
const iconName = getIconName();
</script>

<template>
  <div class="bhr-alert" :class="{ [alertTypeClass]: true }">
    <template v-if="slots.top">
      <slot name="top"></slot>
    </template>

    <div class="flex">
      <span class="mr-4">
        <Icon :color="iconColor" :name="iconName" :size="32" />
      </span>

      <div class="grow pt-1.5">
        <slot />
      </div>
    </div>

    <div v-if="slots.extra" class="mt-4">
      <slot name="extra"></slot>
    </div>
  </div>
</template>
