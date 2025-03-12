<script setup lang="ts">
import InputSelect from '@admin-fe/component/form/InputSelect.vue';
import type { InputStore } from '@admin-fe/composables';
import { useInputStore } from '@admin-fe/composables';
import { validators } from '@admin-fe/form';
import { inject, ref } from 'vue';
import {
  collectAllSelectOptionsFromFileTypes,
  getOptgroupsFromFileTypes,
  getSelectOptionsFromFileTypes,
} from '../helper';
import type { PublicationFileTypes } from '../interface';

interface Props {
  options: PublicationFileTypes;
  value: string;
}

const props = withDefaults(defineProps<Props>(), {
  options: () => ({}),
  value: '',
});

const fileTypeOptions = getSelectOptionsFromFileTypes(props.options);
const fileTypeOptgroups = getOptgroupsFromFileTypes(props.options);
const allSelectedOptions = collectAllSelectOptionsFromFileTypes(props.options);
const isVisible = allSelectedOptions.length > 1;

if (!isVisible) {
  const inputStore = useInputStore(
    'type',
    'Soort document',
    ref(allSelectedOptions[0]?.value),
  );
  (inject('form') as { addInput: (inputStore: InputStore) => void })?.addInput(
    inputStore,
  );
}
</script>

<template>
  <InputSelect
    v-if="isVisible"
    :options="fileTypeOptions"
    :optgroups="fileTypeOptgroups"
    :validators="[validators.required()]"
    :value="props.value"
    emptyLabel="Kies een documentsoort"
    helpText="Wat is de rol van dit document in het Woo-proces?"
    label="Soort document"
    name="type"
  />
</template>
