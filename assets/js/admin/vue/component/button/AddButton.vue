<script setup lang="ts">
import Icon from '@admin-fe/component/Icon.vue';

const emit = defineEmits<{ click: [] }>();
</script>

<template>
  <button @click="emit('click')" class="bhr-btn-ghost-primary" type="button">
    <Icon
      class="bhr-btn__icon-left"
      color="fill-current"
      name="plus"
      :size="20"
    />
    <slot></slot>
  </button>
</template>
