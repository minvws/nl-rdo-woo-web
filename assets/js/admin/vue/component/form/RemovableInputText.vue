<script setup lang="ts">
import { validators as validatorFunctions } from '@admin-fe/form';
import {
  InputStore,
  useInputAriaDescribedBy,
  useInputStore,
} from '@admin-fe/composables';
import { uniqueId } from '@js/utils';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import RemovableInput from './RemovableInput.vue';

interface Props {
  autoFocus?: boolean;
  canDelete?: boolean;
  e2eName?: string;
  forbiddenValues?: string[];
  label: string;
  minLength?: number;
  name?: string;
  value?: string;
}

interface Emits {
  delete: [InputStore];
  mounted: [InputStore];
  update: [string];
}

const props = withDefaults(defineProps<Props>(), {
  autoFocus: false,
  canDelete: false,
  forbiddenValues: () => [],
  name: '',
  value: '',
});

const emit = defineEmits<Emits>();

const inputElement = useTemplateRef<HTMLInputElement>('inputElement');
const inputId = `${uniqueId('input')}`;
const valueRef = ref(props.value);

const inputE2eName = computed(() =>
  props.e2eName ? `${props.e2eName}-input` : undefined,
);

const createValidators = () => {
  const validators = [validatorFunctions.required()];

  if (props.forbiddenValues.length > 0) {
    validators.push(validatorFunctions.forbidden(props.forbiddenValues));
  }

  if (props.minLength) {
    validators.push(validatorFunctions.minLength(props.minLength));
  }

  return validators;
};

const onDelete = () => {
  emit('delete', inputStore);
};

const onBlur = () => {
  inputStore.markAsTouched();
};

const onUpdate = () => {
  emit('update', valueRef.value);
};

const inputStore = useInputStore(
  props.name,
  props.label,
  valueRef,
  createValidators(),
);

const ariaDescribedBy = computed(() =>
  useInputAriaDescribedBy(inputId, '', inputStore.hasVisibleErrors),
);

const inputClass = computed(() => ({
  'bhr-input-text': true,
  'bhr-input-text--invalid': inputStore.hasVisibleErrors,
}));

onMounted(() => {
  if (props.autoFocus) {
    inputElement.value?.focus();
  }
  emit('mounted', inputStore);
});

watch(
  () => props.forbiddenValues,
  () => {
    inputStore.setValidators(createValidators());
  },
);
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
    <input
      @blur="onBlur"
      @input="onUpdate"
      :aria-describedby="ariaDescribedBy"
      :class="inputClass"
      :data-e2e-name="inputE2eName"
      :id="inputId"
      :name="props.name"
      ref="inputElement"
      type="text"
      v-model="valueRef"
    />
  </RemovableInput>
</template>
