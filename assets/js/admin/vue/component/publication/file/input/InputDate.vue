<script setup lang="ts">
import InputText from '@admin-fe/component/form/InputText.vue';
import { validators } from '@admin-fe/form';

interface Props {
  value?: string;
}

const props = withDefaults(defineProps<Props>(), {
  value: '',
});
</script>

<template>
  <InputText
    :validators="[validators.dateMaxUntilToday(), validators.required()]"
    :value="props.value"
    class="sm:max-w-40"
    helpText="De datering die in het document wordt gebruikt."
    label="Formele datum"
    name="formalDate"
    type="date"
  />
</template>
