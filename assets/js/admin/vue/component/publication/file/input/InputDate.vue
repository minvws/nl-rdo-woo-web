<script setup lang="ts">
import InputText from '@admin-fe/component/form/InputText.vue';
import { validators } from '@admin-fe/form';
import { computed } from 'vue';

interface Props {
  label?: string;
  value?: string;
}

const props = withDefaults(defineProps<Props>(), {
  value: '',
});

const label = computed(() => props.label ?? 'Formele datum');
</script>

<template>
  <InputText
    :validators="[validators.dateMaxUntilToday(), validators.required()]"
    :value="props.value"
    class="sm:max-w-40"
    helpText="De datering die in het document wordt gebruikt."
    :label="label"
    name="formalDate"
    type="date"
  />
</template>
