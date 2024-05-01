<script setup>
  import InputSelect from '@admin-fe/component/form/InputSelect.vue';
  import { validators } from '@admin-fe/form';
  import { getOptgroupsFromDocumentTypes, getSelectOptionsFromDocumentTypes } from './helper';

  const props = defineProps({
    options: {
      type: Array,
      required: true,
      default: () => [],
    },
    value: {
      type: String,
      required: true,
      default: '',
    }
  });

  const documentTypeOptions = getSelectOptionsFromDocumentTypes(props.options);
  const documentTypeOptgroups = getOptgroupsFromDocumentTypes(props.options);
</script>

<template>
  <InputSelect
    :options="documentTypeOptions"
    :optgroups="documentTypeOptgroups"
    :validators="[
      validators.required(),
    ]"
    :value="props.value"
    emptyLabel="Kies een documentsoort"
    helpText="Wat is de rol van dit document in het Woo-proces?"
    label="Soort document"
    name="type"
  />
</template>
