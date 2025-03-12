<script setup lang="ts">
import Icon from '@admin-fe/component/Icon.vue';
import { useTemplateRef } from 'vue';

const wrapperElement = useTemplateRef('wrapperElement');

defineExpose({
  setFocus: () => wrapperElement.value?.focus(),
});
</script>

<template>
  <div class="mb-4 text-center" tabindex="-1" ref="wrapperElement">
    <Icon
      class="animate-spin"
      color="fill-bhr-ocean-boat-blue"
      name="loader"
      :size="48"
    />
    <p class="mt-4">Bezig met controleren</p>
  </div>
</template>
