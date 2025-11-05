<script setup lang="ts">
import { getHelpId } from '@admin-fe/form';
import { uniqueId } from '@js/utils';

interface Props {
  inputId?: string;
}

const props = withDefaults(defineProps<Props>(), {
  inputId: uniqueId('help'),
});

const id = getHelpId(props.inputId);
</script>

<template>
  <div class="bhr-form-help" :id="id">
    <slot />
  </div>
</template>
