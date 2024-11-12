<script setup>
import { getHelpId } from '@admin-fe/form';
import { uniqueId } from '@js/utils';

const props = defineProps({
  inputId: {
    type: String,
  },
});

const id = getHelpId(props.inputId || uniqueId('help'));
</script>

<template>
  <div class="bhr-form-help text-lg pb-2" :id="id">
    <slot />
  </div>
</template>
