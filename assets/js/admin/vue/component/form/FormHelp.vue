<script setup>
  import { getHelpId } from '@admin-fe/form';

  const props = defineProps({
    inputId: {
      type: String,
      required: true
    },
  });

  const id = getHelpId(props.inputId);
</script>

<template>
  <div class="bhr-form-help text-lg pb-2" :id="id">
      <slot />
  </div>
</template>
