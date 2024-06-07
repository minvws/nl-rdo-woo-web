<script setup>
  import ErrorMessages from './ErrorMessages.vue';
  import FormHelp from './FormHelp.vue';
  import { uniqueId } from '@js/utils';
  import { computed, onMounted, ref, watch } from 'vue';

  const emit = defineEmits(['update']);

  const props = defineProps({
    buttonText: {
      type: String,
      required: true,
    },
    buttonTextMultiple: {
      type: String,
      required: false,
    },
    errors: {
      type: Array,
      default: () => [],
    },
    helpText: {
      type: String,
      required: false,
    },
    isInvalid: {
      type: Boolean,
      default: false,
    },
    legend: {
      type: String,
      required: true,
    },
    minLength: {
      type: Boolean,
      required: false,
      default: 0,
    },
    options: {
      type: Array,
      required: true,
      default: () => [],
    },
    values: {
      type: Array,
      default: () => [],
    },
  });

  const createItem = (value = '') => ({
    id: uniqueId(),
    value,
  });

  const getItemsFromProps = () => {
    if (props.values.length > 0) {
      return props.values.map((value) => createItem(value));
    }

    return Array.from({ length: props.minLength }).map(() => createItem());
  };

  const addItemElement = ref(null);
  const items = ref(getItemsFromProps());

  const addItem = () => {
    items.value.push(createItem());
    emitUpdate();
  }

  const deleteItem = (itemId) => {
    items.value = items.value.filter((item) => item.id !== itemId);
    emitUpdate();
    addItemElement.value?.focus();
  };

  const updateItem = (value, itemId) => {
    items.value.map((item) => {
      if (item.id === itemId) {
        item.value = value;
      }
      return item;
    });
    emitUpdate();
  };

  const canDeleteItem = computed(() => items.value.length > props.minLength);
  const getOtherValues = (itemId) => items.value.filter((item) => item.id !== itemId).map((item) => item.value);

  const emitUpdate = () => {
    emit('update', items.value);
  }

  const buttonText = computed(() => {
    if (items.value.length > 0) {
      return props.buttonTextMultiple || props.buttonText;
    }

    return props.buttonText;
  });

  watch(() => props.values, () => {
    items.value = getItemsFromProps();
    emitUpdate();
  });

  onMounted(() => {
    emitUpdate();
  });

  defineExpose({
    canDeleteItem,
    deleteItem,
    getOtherValues,
    updateItem,
  });
</script>

<template>
  <div class="bhr-form-row" :class="{ 'bhr-form-row--invalid': props.isInvalid }">
    <fieldset class="flex flex-col gap-2">
      <legend class="bhr-label">{{ props.legend }} <span class="font-normal" v-if="props.minLength === 0"> (optioneel)</span></legend>

      <FormHelp v-if="props.helpText">{{ props.helpText }}</FormHelp>

      <ErrorMessages :messages="props.errors" />

      <slot />
    </fieldset>

    <button
      @click="addItem"
      class="font-bold text-bhr-davys-grey text-lg mt-2"
      ref="addItemElement"
      type="button"
    >+ {{ buttonText }}</button>
  </div>
</template>
