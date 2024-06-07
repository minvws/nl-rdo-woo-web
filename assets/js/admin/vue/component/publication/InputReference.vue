<script setup>
  import InputText from '@admin-fe/component/form/InputText.vue';

  const props = defineProps({
    value: {
      type: String,
      required: false,
      default: '',
    },
  });
</script>

<template>
  <InputText
    :required="false"
    :value="props.value"
    label="Referentienummer bestand"
    name="internalReference"
  />
</template>
