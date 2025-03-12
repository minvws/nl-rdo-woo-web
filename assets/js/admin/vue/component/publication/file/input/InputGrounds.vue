<script setup lang="ts">
import MultiSelect from '@admin-fe/component/form/MultiSelect.vue';
import { SelectOptions } from '@admin-fe/form/interface';
import type { GroundOptions } from '../interface';

interface Props {
  options: GroundOptions;
  values: string[];
}

const props = withDefaults(defineProps<Props>(), {
  options: () => [],
  values: () => [],
});

const options: SelectOptions = props.options.reduce<SelectOptions>(
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
