<script setup>
  import MultiSelect from '@admin-fe/component/form/MultiSelect.vue';

  const props = defineProps([
    'buttonText',
    'buttonTextMultiple',
    'helpText',
    'label',
    'legend',
    'minLength',
    'maxLength',
    'name',
    'options',
    'submitErrors',
    'values',
  ]);
</script>

<template>
  <MultiSelect v-bind="props" />
</template>
