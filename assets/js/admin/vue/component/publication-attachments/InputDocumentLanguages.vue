<script setup>
  import InputSelect from '@admin-fe/component/form/InputSelect.vue';
  import { validators } from '@admin-fe/form';

  const props = defineProps({
    options: {
      type: Array,
      required: true,
      default: () => [],
    },
    value: {
      type: String,
      required: false,
      default: '',
    },
  });
</script>

<template>
  <InputSelect
    :options="props.options"
    :validators="[
      validators.required(),
    ]"
    :value="value"
    label="Taal van het document"
    name="language"
  />
</template>
