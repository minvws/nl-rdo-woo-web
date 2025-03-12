<script setup lang="ts">
import InputSelect from '@admin-fe/component/form/InputSelect.vue';
import { validators } from '@admin-fe/form';
import type { SelectOptions } from '@admin-fe/form/interface';

interface Props {
  options: SelectOptions;
  value?: string;
}

const props = withDefaults(defineProps<Props>(), {
  options: () => [],
  value: '',
});
</script>

<template>
  <InputSelect
    :options="props.options"
    :validators="[validators.required()]"
    :value="value"
    label="Taal van het document"
    name="language"
  />
</template>
