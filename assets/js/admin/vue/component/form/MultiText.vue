<script setup lang="ts">
import type { MultiInputItem } from './interface';
import {
  FormStore,
  InputStore,
  useMultiInputStore,
} from '@admin-fe/composables';
import { createName, getOtherValues, shouldAutoFocus } from '@admin-fe/form';
import { computed, inject, ref, useTemplateRef } from 'vue';
import MultiInput from './MultiInput.vue';
import RemovableInputText from './RemovableInputText.vue';

interface Props {
  buttonText: string;
  buttonTextMultiple?: string;
  e2eName?: string;
  helpText?: string;
  immutableValues?: string[];
  label: string;
  legend: string;
  minChars?: number;
  minLength?: number;
  maxLength?: number;
  name: string;
  submitErrors?: string[];
  values?: string[];
}

const props = withDefaults(defineProps<Props>(), {
  immutableValues: () => [],
  submitErrors: () => [],
  values: () => [],
});

const items = ref<MultiInputItem[]>([]);
const multiInputComponent = useTemplateRef<typeof MultiInput>(
  'multiInputComponent',
);
const numberOfImmutableValues = computed(() => props.immutableValues.length);

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
    :e2e-name="props.e2eName"
    :errors="props.submitErrors"
    :help-text="props.helpText"
    :is-invalid="
      multiInputStore.hasVisibleErrors || props.submitErrors.length > 0
    "
    :immutable-values="props.immutableValues"
    :legend="props.legend"
    :max-length="props.maxLength"
    :min-length="props.minLength"
    :name="props.name"
    :options="[]"
    :values="props.values"
    ref="multiInputComponent"
  >
    <RemovableInputText
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
      :e2e-name="props.e2eName"
      :forbidden-values="[
        ...getOtherValues(item.id, items),
        ...props.immutableValues,
      ]"
      :key="item.id"
      :label="`${props.label} ${numberOfImmutableValues + index + 1}`"
      :min-length="props.minChars"
      :name="createName(props.name, numberOfImmutableValues + index)"
      :value="item.value"
    />
  </MultiInput>
</template>
