<script setup>
import { useMultiInputStore } from '@admin-fe/composables';
import { createName, getOtherValues, shouldAutoFocus } from '@admin-fe/form';
import { computed, inject, ref } from 'vue';
import Combobox from './Combobox.vue';
import MultiInput from './MultiInput.vue';

const props = defineProps({
  buttonText: {
    type: String,
    required: true,
  },
  buttonTextMultiple: {
    type: String,
    required: false,
  },
  helpText: {
    type: String,
    required: false,
  },
  label: {
    type: String,
    required: true,
  },
  minLength: {
    type: Number,
    required: false,
    default: 0,
  },
  legend: {
    type: String,
    required: true,
  },
  name: {
    type: String,
    required: true,
  },
  options: {
    type: Array,
    required: true,
    default: () => [],
  },
  submitErrors: {
    type: Array,
    default: () => [],
  },
  values: {
    type: Array,
    default: () => [],
  },
});

const items = ref([]);
const multiInputComponent = ref(null);

const updateItem = (value, itemId) => {
  multiInputComponent.value?.updateItem(value, itemId);
};

const deleteItem = (inputStore, itemId) => {
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
inject('form')?.addInput(multiInputStore);

const onItemsUpdate = (updatedItems) => {
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
      :errors="errors"
      :forbidden-values="getOtherValues(item.id, items)"
      :key="item.id"
      :label="`${props.label} ${index + 1}`"
      :name="createName(props.name, index)"
      :options="props.options"
      :value="item.value"
    />
  </MultiInput>
</template>
