<script setup lang="ts">
import {
  type FormStore,
  type InputStore,
  useMultiInputStore,
} from '@admin-fe/composables';
import { createName, getOtherValues, shouldAutoFocus } from '@admin-fe/form';
import { computed, inject, ref, useTemplateRef } from 'vue';
import Combobox from './Combobox.vue';
import MultiInput from './MultiInput.vue';
import type { MultiInputItem } from './interface';

interface Props {
  buttonText: string;
  buttonTextMultiple?: string;
  helpText?: string;
  label: string;
  legend: string;
  maxLength?: number;
  maxValueLength?: number;
  minLength?: number;
  minValueLength?: number;
  name: string;
  options: string[];
  submitErrors?: string[];
  values?: string[];
}

const props = withDefaults(defineProps<Props>(), {
  minLength: 0,
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
    :min-length="props.minLength"
    :max-length="props.maxLength"
    :options="props.options"
    :values="props.values"
    ref="multiInputComponent"
  >
    <Combobox
      v-for="(item, index) in items"
      @delete="(comboboxInputStore) => deleteItem(comboboxInputStore, item.id)"
      @mounted="
        (comboboxInputStore) =>
          multiInputStore.addInputStore(comboboxInputStore)
      "
      @update="(value) => updateItem(value, item.id)"
      :auto-focus="shouldAutoFocus(index, items, props.minLength)"
      :can-delete="canDeleteItem"
      :forbidden-values="getOtherValues(item.id, items)"
      :key="item.id"
      :label="`${props.label} ${index + 1}`"
      :max-length="props.maxValueLength"
      :min-length="props.minValueLength"
      :name="createName(props.name, index)"
      :options="props.options"
      :value="item.value"
    />
  </MultiInput>
</template>
