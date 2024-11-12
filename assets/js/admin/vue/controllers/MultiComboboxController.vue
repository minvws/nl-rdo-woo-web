<script setup>
import MultiCombobox from '@admin-fe/component/form/MultiCombobox.vue';

const props = defineProps([
  'buttonText',
  'buttonTextMultiple',
  'helpText',
  'label',
  'legend',
  'minLength',
  'name',
  'options',
  'submitErrors',
  'values',
]);
</script>

<template>
  <MultiCombobox v-bind="props" />
</template>
