<script setup>
  import InputErrors from './InputErrors.vue';
  import Icon from '@admin-fe/component/Icon.vue';
  import { useInputAriaDescribedBy, useInputStore } from '@admin-fe/composables';
  import { validators } from '@admin-fe/form';
  import { uniqueId } from '@js/utils';
  import { computed, onMounted, ref, watch } from 'vue';

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

  const createValidators = (forbiddenValues, errors) => {
    const inputValidators = [validators.required()];
    if (forbiddenValues.length > 0) {
      inputValidators.push(validators.forbidden(forbiddenValues));
    } else if (errors) {
      inputValidators.push(validators.forbidden(errors));
    }

    return inputValidators;
  }

  watch(() => props.value, (newValue) => {
    value.value = newValue;
  });

  watch(() => props.forbiddenValues, (newForbiddenValues, oldForbiddenValues) => {
    if (JSON.stringify(newForbiddenValues) === JSON.stringify(oldForbiddenValues)) {
      // forbiddenValues has not changed
      return;
    }

    inputStore.setValidators(createValidators(newForbiddenValues));
  });

  const inputStore = useInputStore(props.name, props.label, value, createValidators(props.forbiddenValues));
  const ariaDescribedBy = computed(() => useInputAriaDescribedBy(inputId, undefined, inputStore.hasVisibleErrors));
  const validatorMessages = {
    forbidden: () => `De waarde "${value.value}" is meerdere keren ingevuld.`,
  };

  onMounted(() => {
    if (props.autoFocus) {
      inputElement.value.focus();
    }
    emit('mounted', inputStore);
  });
</script>

<template>
  <div>
    <label class="sr-only" :for="inputId">{{ label }}</label>

    <InputErrors
      class="mt-2"
      :errors="[...inputStore.errors, ...errors]"
      :inputId="inputId"
      :validator-messages="validatorMessages"
      :value="value"
      v-if="inputStore.hasVisibleErrors || errors.length > 0"
    />

    <div class="flex">
      <div class="grow">
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
              v-for="option in options"
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
      </div>

      <button
        v-if="canDelete"
        @click="onDelete"
        class="bhr-button cursor-pointer shadow-none text-bhr-independence hover-focus:text-bhr-maximum-red"
        type="button"
      >
        <span class="sr-only">Verwijder {{ props.label }}</span>
        <Icon
          color="fill-current"
          name="trash-bin"
          size="18"
        />
      </button>
    </div>
  </div>
</template>
