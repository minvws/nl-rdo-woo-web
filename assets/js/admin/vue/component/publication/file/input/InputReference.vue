<script setup lang="ts">
import InputText from '@admin-fe/component/form/InputText.vue';

interface Props {
  value?: string;
}

const props = withDefaults(defineProps<Props>(), {
  value: '',
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
