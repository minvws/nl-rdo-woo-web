<script setup>
  import { useInputAriaDescribedBy, useInputStore } from '@admin-fe/composables';
  import { validators } from '@admin-fe/form';
  import { uniqueId } from '@js/utils';
  import { computed, onMounted, ref, watch } from 'vue';
  import RemovableInput from './RemovableInput.vue';

  const emit = defineEmits(['delete', 'mounted', 'update']);

  const props = defineProps({
    autoFocus: {
      type: Boolean,
      default: false,
    },
    canDelete: {
      type: Boolean,
      default: false,
    },
    emptyLabel: {
      type: String,
      required: false,
      default: 'Kies een optie',
    },
    errors: {
      type: Array,
      default: () => [],
    },
    forbiddenValues: {
      type: Array,
      default: () => [],
    },
    label: {
      type: String,
      required: true,
    },
    name: {
      type: String,
      default: '',
    },
    options: {
      type: Array,
      required: true,
      default: () => [],
    },
    optGroups: {
      type: Array,
      required: true,
      default: () => [],
    },
    value: {
      type: String,
      required: false,
      default: '',
    },
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

    return options.value.filter((option) => !props.forbiddenValues.includes(option.value));
  });

  const errors = ref([]);
  const inputElement = ref(null);
  const inputId = `${uniqueId('input')}`;
  const value = ref(props.value);

  const onDelete = () => {
    emit('delete', inputStore);
  }

  const onChange = () => {
    emit('update', value.value);
    inputStore.markAsTouched();
  }

  watch(() => props.value, (newValue) => {
    value.value = newValue;
  });

  const inputStore = useInputStore(props.name, props.label, value, [validators.required()]);
  const ariaDescribedBy = computed(() => useInputAriaDescribedBy(inputId, undefined, inputStore.hasVisibleErrors));

  onMounted(() => {
    if (props.autoFocus) {
      inputElement.value.focus();
    }
    emit('mounted', inputStore);
  });
</script>

<template>
  <RemovableInput
    @delete="onDelete"
    :are-errors-visible="inputStore.hasVisibleErrors || errors.length > 0"
    :can-delete="canDelete"
    :errors="[...inputStore.errors, ...errors]"
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
        v-model="value"
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
          v-for="optgroup in props.optgroups"
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
