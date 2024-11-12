<script setup>
import MultiSelect from '@admin-fe/component/form/MultiSelect.vue';

const props = defineProps({
  options: {
    type: Array,
    required: true,
    default: () => [],
  },
  values: {
    type: Array,
    required: false,
    default: () => [],
  },
});

const options = props.options.reduce(
  (collected, option) => [
    ...collected,
    { label: `${option.citation} ${option.label}`, value: option.citation },
  ],
  [],
);
</script>

<template>
  <MultiSelect
    buttonText="Weigeringsgrond toevoegen"
    buttonTextMultiple="Nog een weigeringsgrond toevoegen"
    helpText="Zijn in dit document gegevens gelakt? Kies dan de gebruikte weigeringsgronden."
    legend="Weigeringsgronden"
    label="Weigeringsgrond"
    name="grounds"
    :options="options"
    :values="props.values"
  />
</template>
