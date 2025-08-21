<script setup lang="ts">
import {
  FormStore,
  InputStore,
  useMultiInputStore,
} from '@admin-fe/composables';
import { createName, getOtherValues, shouldAutoFocus } from '@admin-fe/form';
import type { SelectOptions } from '@admin-fe/form/interface';
import { computed, inject, ref, useTemplateRef } from 'vue';
import MultiInput from './MultiInput.vue';
import RemovableSelect from './RemovableSelect.vue';
import type { MultiInputItem } from './interface';

interface Props {
  buttonText: string;
  buttonTextMultiple?: string;
  helpText?: string;
  label: string;
  minLength?: number;
  maxLength?: number;
  legend: string;
  name: string;
  options: SelectOptions;
  submitErrors?: string[];
  values: string[];
}

const props = withDefaults(defineProps<Props>(), {
  options: () => [],
  submitErrors: () => [],
  values: () => [],
});

const items = ref<MultiInputItem[]>([]);
const multiInputComponent = useTemplateRef<typeof MultiInput>(
  'multiInputComponent',
);

const updateItem = (value: string, itemId: string) => {
  multiInputComponent.value?.updateItem(value, itemId);
};

const deleteItem = (inputStore: InputStore, itemId: string) => {
  multiInputComponent.value?.deleteItem(itemId);
  multiInputStore.removeInputStore(inputStore);
  multiInputStore.makeDirty();
};

const canDeleteItem = computed(
  () => multiInputComponent.value?.canDeleteItem || false,
);

const multiInputStore = useMultiInputStore(
  props.name,
  props.legend,
  computed(() => items.value.map((item) => item.value)),
);
(inject('form') as FormStore)?.addInput(multiInputStore);

const onItemsUpdate = (updatedItems: MultiInputItem[]) => {
  items.value = updatedItems;
};
</script>

<template>
  <MultiInput
    @update="onItemsUpdate"
    :button-text="props.buttonText"
    :button-text-multiple="props.buttonTextMultiple"
    :errors="props.submitErrors"
    :help-text="props.helpText"
    :is-invalid="
      multiInputStore.hasVisibleErrors || props.submitErrors.length > 0
    "
    :legend="props.legend"
    :max-length="props.maxLength"
    :min-length="props.minLength"
    :options="props.options.map((option) => option.value)"
    :values="props.values"
    ref="multiInputComponent"
  >
    <RemovableSelect
      v-for="(item, index) in items"
      @delete="
        (selectInputStore: InputStore) => deleteItem(selectInputStore, item.id)
      "
      @mounted="
        (selectInputStore: InputStore) =>
          multiInputStore.addInputStore(selectInputStore)
      "
      @update="(value: string) => updateItem(value, item.id)"
      :auto-focus="shouldAutoFocus(index, items, props.minLength)"
      :can-delete="canDeleteItem"
      :forbidden-values="getOtherValues(item.id, items)"
      :key="item.id"
      :label="`${props.label} ${index + 1}`"
      :name="createName(props.name, index)"
      :options="props.options"
      :value="item.value"
    />
  </MultiInput>
</template>
