<script setup>
import InputSelect from '@admin-fe/component/form/InputSelect.vue';
import { useInputStore } from '@admin-fe/composables';
import { validators } from '@admin-fe/form';
import {
  collectAllSelectOptionsFromDocumentTypes,
  getOptgroupsFromDocumentTypes,
  getSelectOptionsFromDocumentTypes,
} from './helper';
import { inject, ref } from 'vue';

const props = defineProps({
  options: {
    type: Object,
    required: true,
    default: () => ({}),
  },
  value: {
    type: String,
    required: true,
    default: '',
  },
});

const documentTypeOptions = getSelectOptionsFromDocumentTypes(props.options);
const documentTypeOptgroups = getOptgroupsFromDocumentTypes(props.options);
const allSelectedOptions = collectAllSelectOptionsFromDocumentTypes(
  props.options,
);
const isVisible = allSelectedOptions.length > 1;

if (!isVisible) {
  const inputStore = useInputStore(
    'type',
    'Soort document',
    ref(allSelectedOptions[0]?.value),
  );
  inject('form')?.addInput(inputStore);
}
</script>

<template>
  <InputSelect
    v-if="isVisible"
    :options="documentTypeOptions"
    :optgroups="documentTypeOptgroups"
    :validators="[validators.required()]"
    :value="props.value"
    emptyLabel="Kies een documentsoort"
    helpText="Wat is de rol van dit document in het Woo-proces?"
    label="Soort document"
    name="type"
  />
</template>
