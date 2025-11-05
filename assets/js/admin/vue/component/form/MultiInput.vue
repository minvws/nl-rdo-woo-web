<script setup lang="ts">
import { uniqueId } from '@js/utils';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import ErrorMessages from './ErrorMessages.vue';
import FormHelp from './FormHelp.vue';
import ImmutableInputs from './ImmutableInputs.vue';
import ImmutableValues from './ImmutableValues.vue';
import Icon from '../Icon.vue';
import type { MultiInputItem } from './interface';

interface Props {
  buttonText: string;
  buttonTextMultiple?: string;
  e2eName?: string;
  errors: string[];
  helpText?: string;
  immutableValues?: string[];
  isInvalid: boolean;
  legend: string;
  minLength?: number;
  maxLength?: number;
  name?: string;
  options: string[];
  values: string[];
}

interface Emits {
  update: [MultiInputItem[]];
}

const props = withDefaults(defineProps<Props>(), {
  errors: () => [],
  immutableValues: () => [],
  isInvalid: false,
  minLength: 0,
  name: '',
  options: () => [],
  values: () => [],
});

const emit = defineEmits<Emits>();

const createItem = (value = ''): MultiInputItem => ({
  id: uniqueId(),
  value,
});

const getItemsFromProps = () => {
  if (props.values.length > 0) {
    return props.values.map((value) => createItem(value));
  }

  return Array.from({
    length: Math.max(props.minLength - props.immutableValues.length, 0),
  }).map(() => createItem());
};

const addItemElement = useTemplateRef<HTMLButtonElement>('addItemElement');
const items = ref(getItemsFromProps());

const addItem = () => {
  items.value.push(createItem());
  emitUpdate();
};

const deleteItem = (itemId: string) => {
  items.value = items.value.filter((item) => item.id !== itemId);
  emitUpdate();
  addItemElement.value?.focus();
};

const updateItem = (value: string, itemId: string) => {
  items.value.map((item) => {
    if (item.id === itemId) {
      // eslint-disable-next-line no-param-reassign
      item.value = value;
    }
    return item;
  });
  emitUpdate();
};

const canDeleteItem = computed(
  () => items.value.length + props.immutableValues.length > props.minLength,
);
const canAddItem = computed(() => {
  const optionsLength = props.options.length;
  const itemsLength = items.value.length;
  const immutableValuesLength = props.immutableValues.length;

  if (props.maxLength) {
    return itemsLength + immutableValuesLength < props.maxLength;
  }

  if (
    optionsLength > 0 &&
    itemsLength + immutableValuesLength >= optionsLength
  ) {
    return false;
  }

  return true;
});
const getOtherValues = (itemId: string) =>
  items.value.filter((item) => item.id !== itemId).map((item) => item.value);

const emitUpdate = () => {
  emit('update', items.value);
};

const buttonText = computed(() => {
  if (items.value.length + props.immutableValues.length > 0) {
    return props.buttonTextMultiple ?? props.buttonText;
  }

  return props.buttonText;
});

const buttonE2eName = computed(() =>
  props.e2eName ? `${props.e2eName}-button` : undefined,
);

watch(
  () => props.values,
  () => {
    items.value = getItemsFromProps();
    emitUpdate();
  },
);

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
  <div
    class="bhr-form-row"
    :class="{ 'bhr-form-row--invalid': props.isInvalid }"
  >
    <fieldset class="flex flex-col gap-2">
      <legend class="bhr-label">
        {{ props.legend }}
        <span class="bhr-optional" v-if="props.minLength === 0">
          <span class="sr-only">(</span>optioneel<span class="sr-only"
            >)</span
          ></span
        >
      </legend>

      <FormHelp v-if="props.helpText">{{ props.helpText }}</FormHelp>

      <ImmutableValues :values="props.immutableValues" />

      <ImmutableInputs :name="props.name" :values="props.immutableValues" />

      <div aria-live="assertive">
        <ErrorMessages :messages="props.errors" />
      </div>

      <slot />
    </fieldset>

    <button
      v-if="canAddItem"
      @click="addItem"
      :data-e2e-name="buttonE2eName"
      class="bhr-btn-ghost-primary mt-2"
      ref="addItemElement"
      type="button"
    >
      <Icon
        class="bhr-btn__icon-left"
        color="fill-current"
        name="plus"
        :size="24"
      />
      {{ buttonText }}
    </button>
  </div>
</template>
