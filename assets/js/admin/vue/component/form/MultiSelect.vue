<script setup>
  import { useMultiInputStore } from '@admin-fe/composables';
  import { uniqueId } from '@js/utils';
  import { computed, inject, ref, watch } from 'vue';
  import RemovableSelect from './RemovableSelect.vue';
  import ErrorMessages from './ErrorMessages.vue';
  import FormHelp from './FormHelp.vue';

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
      type: Boolean,
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

  const transformedOptions = computed(() => {
    return props.options.map((option) => {
      if (typeof option === 'string') {
        return { label: option, value: option };
      }

      return option;
    });
  });

  const optionLabels = computed(() => transformedOptions.value.map((option) => option.label));

  const addItemButton = ref(null);

  const createItem = (value = '') => ({
    id: uniqueId(),
    value,
  });

  const getItemsFromProps = () => {
    if (props.values.length > 0) {
      return props.values.map((value) => createItem(getDisplayValue(value)));
    }

    return Array.from({ length: props.minLength }).map(() => createItem());
  };

  const getDisplayValue = (fromValue) => {
    if (!fromValue) {
      return '';
    }

    const trimmedValue = fromValue.trim();
    const formattedValue = trimmedValue.toLowerCase();
    const foundOption = transformedOptions.value.find((option) => option.value.toLowerCase() === formattedValue);
    return foundOption ? foundOption.label : trimmedValue;
  }

  const getInputStoreValue = (fromValue) => {
    const trimmedValue = fromValue.trim();
    const formattedValue = trimmedValue.toLowerCase();
    const foundOption = transformedOptions.value.find((option) => option.label.toLowerCase() === formattedValue);
    return foundOption ? foundOption.value : trimmedValue;
  }

  const items = ref(getItemsFromProps());
  const value = computed(() => items.value.map((item) => getInputStoreValue(item.value)));

  const getOtherValues = (itemId) => items.value.filter((item) => item.id !== itemId).map((item) => item.value);

  const addItem = () => {
    items.value.push(createItem());
  }

  const createName = (index) => {
    const regex = /\[(\d+)\]/;
    if (!props.name.match(regex)) {
      return `${props.name}[${index}]`;
    }
    return props.name.replace(regex, `[${index}]`)
  };

  const deleteItem = (itemId) => {
    items.value = items.value.filter((item) => item.id !== itemId);
    addItemButton.value?.focus();
  };

  const updateItem = (value, itemId) => {
    items.value.map((item) => {
      if (item.id === itemId) {
        item.value = value;
      }
      return item;
    });
  };

  const onDelete = (inputStore, itemId) => {
    deleteItem(itemId)
    multiInputStore.removeInputStore(inputStore);
    multiInputStore.makeDirty();
  };

  const canDeleteItem = computed(() => items.value.length > props.minLength);

  const buttonText = computed(() => {
    if (items.value.length > 0) {
      return props.buttonTextMultiple || props.buttonText;
    }

    return props.buttonText;
  });

  const multiInputStore = useMultiInputStore(props.name, props.legend, value);
  inject('form')?.addInput(multiInputStore);

  watch(() => props.values, () => {
    items.value = getItemsFromProps();
  });

  const shouldAutoFocus = (index) => {
    const numberOfItems = items.value.length;
    if (props.minLength == numberOfItems) {
      return false;
    }

    if (items.value[index].value) {
      return false;
    }

    return index === numberOfItems - 1;
  }
</script>

<template>
  <div class="bhr-form-row" :class="{ 'bhr-form-row--invalid': multiInputStore.hasVisibleErrors || props.submitErrors.length > 0 }">
    <fieldset class="flex flex-col gap-2">
      <legend class="bhr-label">{{ props.legend }} <span class="font-normal" v-if="!props.isRequired"> (optioneel)</span></legend>

      <FormHelp v-if="props.helpText">{{ props.helpText }}</FormHelp>

      <ErrorMessages :messages="props.submitErrors" />

      <RemovableSelect
        @delete="(comboboxInputStore) => onDelete(comboboxInputStore, item.id)"
        @mounted="(comboboxInputStore) => multiInputStore.addInputStore(comboboxInputStore)"
        @update="(value) => updateItem(value, item.id)"
        v-for="(item, index) in items"
        :auto-focus="shouldAutoFocus(index, item.value)"
        :can-delete="canDeleteItem"
        :errors="errors"
        :forbidden-values="getOtherValues(item.id)"
        :key="item.id"
        :label="`${props.label} ${index + 1}`"
        :name="createName(index)"
        :options="optionLabels"
        :value="item.value"
      />
    </fieldset>

    <button
      @click="addItem"
      class="font-bold text-bhr-davys-grey text-lg mt-2"
      ref="addItemButton"
      type="button"
    >+ {{ buttonText }}</button>
  </div>
</template>
