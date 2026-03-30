<script setup lang="ts">
import { useInputAriaDescribedBy, useInputStore } from '@admin-fe/composables';
import { validators } from '@admin-fe/form';
import { uniqueId } from '@js/utils';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import RemovableInput from './RemovableInput.vue';
import type { Optgroup, SelectOptions } from '@admin-fe/form/interface';

const emit = defineEmits(['delete', 'mounted', 'update']);

interface Props {
  autoFocus?: boolean;
  canDelete?: boolean;
  emptyLabel?: string;
  forbiddenValues?: string[];
  label: string;
  name: string;
  options: SelectOptions;
  optGroups?: Optgroup[];
  value?: string;
}

const props = withDefaults(defineProps<Props>(), {
  autoFocus: false,
  canDelete: false,
  emptyLabel: 'Kies een optie',
  forbiddenValues: () => [],
  name: '',
  options: () => [],
  optGroups: () => [],
  value: '',
});

const options = computed(() => {
  return props.options.map((option) => {
    if (typeof option === 'string') {
      return { label: option, value: option };
    }

    return option;
  });
});

const filteredOptions = computed(() => {
  if (props.forbiddenValues.length === 0) {
    return options.value;
  }

  return options.value.filter(
    (option) => !props.forbiddenValues.includes(option.value),
  );
});

const getDefaultvalue = () => {
  if (props.value) {
    return props.value;
  }

  if (filteredOptions.value.length === 1) {
    return filteredOptions.value[0].value;
  }

  return props.value;
};

const inputElement = useTemplateRef<HTMLSelectElement>('inputElement');
const inputId = `${uniqueId('input')}`;
const valueRef = ref(getDefaultvalue());

const onDelete = () => {
  emit('delete', inputStore);
};

const onChange = () => {
  emit('update', valueRef.value);
  inputStore.markAsTouched();
};

watch(
  () => props.value,
  (newValue) => {
    valueRef.value = newValue;
  },
);

watch(filteredOptions, () => {
  if (valueRef.value) {
    return;
  }

  if (filteredOptions.value.length !== 1) {
    return;
  }

  valueRef.value = filteredOptions.value[0].value;
});

const inputStore = useInputStore(props.name, props.label, valueRef, [
  validators.required(),
]);
const ariaDescribedBy = computed(() =>
  useInputAriaDescribedBy(inputId, '', inputStore.hasVisibleErrors),
);

onMounted(() => {
  if (props.autoFocus) {
    inputElement.value?.focus();
  }
  emit('mounted', inputStore);

  if (filteredOptions.value.length === 1) {
    emit('update', valueRef.value);
  }
});
</script>

<template>
  <RemovableInput
    @delete="onDelete"
    :are-errors-visible="inputStore.hasVisibleErrors"
    :can-delete="props.canDelete"
    :errors="inputStore.errors"
    :id="inputId"
    :label="label"
  >
    <div class="bhr-select">
      <select
        @change="onChange"
        :aria-describedby="ariaDescribedBy"
        :id="inputId"
        :name="name"
        class="bhr-select__select w-full pr-12"
        :class="{ 'bhr-select__select--invalid': inputStore.hasVisibleErrors }"
        ref="inputElement"
        v-model="valueRef"
      >
        <option value="">{{ props.emptyLabel }}</option>
        <option
          v-for="option in filteredOptions"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
        <optgroup
          v-for="optgroup in props.optGroups"
          :key="optgroup.label"
          :label="optgroup.label"
        >
          <option
            v-for="option in optgroup.options"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </optgroup>
      </select>
    </div>
  </RemovableInput>
</template>
