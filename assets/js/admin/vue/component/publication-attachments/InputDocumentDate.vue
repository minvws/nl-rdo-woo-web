<script setup>
  import InputText from '@admin-fe/component/form/InputText.vue';
  import { validators } from '@admin-fe/form';

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
    :validators="[
      validators.dateMaxUntilToday(),
      validators.required(),
    ]"
    :value="props.value"
    class="sm:max-w-[50%]"
    helpText="Bij een brief of besluit: de datering die in het document wordt gebruikt."
    label="Formele datum bijlage"
    name="formalDate"
    type="date"
  />
</template>
