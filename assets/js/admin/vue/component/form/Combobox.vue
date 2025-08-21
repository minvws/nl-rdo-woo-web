<script setup lang="ts">
import Icon from '@admin-fe/component/Icon.vue';
import {
  InputStore,
  useInputAriaDescribedBy,
  useInputStore,
} from '@admin-fe/composables';
import { validators, type InputValidationErrors } from '@admin-fe/form';
import { removeAccents, uniqueId } from '@js/utils';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import RemovableInput from './RemovableInput.vue';

interface Props {
  autoFocus?: boolean;
  canDelete?: boolean;
  errors?: InputValidationErrors;
  forbiddenValues?: string[];
  label: string;
  maxLength?: number;
  minLength?: number;
  name?: string;
  options: string[];
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
  errors: () => [],
  forbiddenValues: () => [],
  name: '',
  options: () => [],
  value: '',
});

const emit = defineEmits<Emits>();

const activeSearchResultIndex = ref(-1);
const inputElement = useTemplateRef<HTMLInputElement>('inputElement');
const inputId = `${uniqueId('input')}`;
const isListVisible = ref(false);
const listId = `${uniqueId('list')}`;
const optionsListElement =
  useTemplateRef<HTMLUListElement>('optionsListElement');
const value = ref(props.value);

const onDelete = () => {
  emit('delete', inputStore);
};

const onUpdate = () => {
  emit('update', value.value);

  activeSearchResultIndex.value = -1;
};

const hideList = () => (isListVisible.value = false);
const showList = () => (isListVisible.value = true);
const toggleList = () => (isListVisible.value = !isListVisible.value);

const hasSearchResults = computed(() => searchResults.value.length > 0);
const hasVisibleList = computed(
  () => isListVisible.value && hasSearchResults.value,
);

const filteredOptions = computed(() => {
  if (props.forbiddenValues.length === 0) {
    return props.options;
  }

  return props.options.filter(
    (option) => !props.forbiddenValues.includes(option),
  );
});

const searchResults = computed(() => {
  if (!props.value) {
    return filteredOptions.value;
  }

  return filteredOptions.value.filter((option) => {
    return removeAccents(option.toLowerCase()).includes(
      removeAccents(props.value.toLowerCase()),
    );
  });
});

const increaseActiveSearchResultIndex = () => {
  if (activeSearchResultIndex.value === searchResults.value.length - 1) {
    activeSearchResultIndex.value = 0;

    return;
  }

  activeSearchResultIndex.value += 1;
};

const decreaseActiveSearchResultIndex = () => {
  if (activeSearchResultIndex.value < 1) {
    activeSearchResultIndex.value = searchResults.value.length - 1;
    return;
  }

  activeSearchResultIndex.value -= 1;
};

const onClick = (option: string) => {
  setValue(option);
  inputElement.value?.focus();
};

const setValue = (inputValue: string) => {
  value.value = inputValue;
  onUpdate();

  hideList();
};

const onAltPlusArrowDown = () => {
  showList();
};

const onArrowDown = () => {
  showList();

  increaseActiveSearchResultIndex();
};

const onArrowUp = () => {
  showList();

  decreaseActiveSearchResultIndex();
};

const onClickOnInputWrapper = () => {
  toggleList();
};

const onEnter = () => {
  if (activeSearchResultIndex.value === -1) {
    hideList();
    return;
  }

  const inputValue = searchResults.value[activeSearchResultIndex.value];
  setValue(inputValue);
};

const onEscape = () => {
  if (isListVisible.value) {
    hideList();
    return;
  }

  value.value = '';
  onUpdate();
};

const getOptionIdByIndex = (index: number) => `${listId}-${index}`;

const getActiveDescendant = () => {
  if (activeSearchResultIndex.value === -1) {
    return undefined;
  }

  scrollSearchResultIntoView(activeSearchResultIndex.value);
  return getOptionIdByIndex(activeSearchResultIndex.value);
};

const scrollSearchResultIntoView = (index: number) => {
  if (index === -1) {
    return;
  }

  const optionElement = optionsListElement.value?.querySelector(
    `#${getOptionIdByIndex(index)}`,
  );
  optionElement?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
};

const isSearchResultActive = (index: number) =>
  index === activeSearchResultIndex.value;

const onBlur = () => {
  inputStore.markAsTouched();

  setTimeout(() => {
    hideList();
  }, 200);
};

const createValidators = (forbiddenValues: string[]) => {
  const inputValidators = [validators.required()];
  if (forbiddenValues.length > 0) {
    inputValidators.push(validators.forbidden(forbiddenValues));
  }

  if (props.minLength) {
    inputValidators.push(validators.minLength(props.minLength));
  }

  return inputValidators;
};

watch(
  () => props.value,
  (newValue) => {
    value.value = newValue;
  },
);

watch(
  () => props.forbiddenValues,
  (newForbiddenValues, oldForbiddenValues) => {
    if (
      JSON.stringify(newForbiddenValues) === JSON.stringify(oldForbiddenValues)
    ) {
      // forbiddenValues has not changed
      return;
    }

    inputStore.setValidators(createValidators(newForbiddenValues));
  },
);

const inputStore = useInputStore(
  props.name,
  props.label,
  value,
  createValidators(props.forbiddenValues),
);
const ariaDescribedBy = computed(() =>
  useInputAriaDescribedBy(inputId, '', inputStore.hasVisibleErrors),
);

onMounted(() => {
  if (props.autoFocus) {
    inputElement.value?.focus();
    showList();
  }
  emit('mounted', inputStore);
});
</script>

<template>
  <RemovableInput
    @delete="onDelete"
    :are-errors-visible="inputStore.hasVisibleErrors || props.errors.length > 0"
    :can-delete="canDelete"
    :errors="[...inputStore.errors, ...props.errors]"
    :id="inputId"
    :label="label"
  >
    <div class="relative">
      <div class="bhr-combobox__input" @click="onClickOnInputWrapper">
        <input
          @blur="onBlur"
          @input="onUpdate"
          @keyup.alt.down="onAltPlusArrowDown"
          @keyup.down.exact="onArrowDown"
          @keypress.enter.stop.prevent="onEnter"
          @keyup.esc="onEscape"
          @keyup.up="onArrowUp"
          :aria-activedescendant="getActiveDescendant()"
          :aria-describedby="ariaDescribedBy"
          aria-autocomplete="list"
          :aria-controls="listId"
          :aria-expanded="hasVisibleList"
          autocomplete="off"
          :id="inputId"
          :name="name"
          class="bhr-input-text w-full pr-12"
          :class="{ 'bhr-input-text--invalid': inputStore.hasVisibleErrors }"
          :maxlength="maxLength"
          ref="inputElement"
          role="combobox"
          type="text"
          v-model="value"
        />
        <button
          v-if="hasSearchResults"
          :aria-controls="listId"
          :aria-expanded="hasVisibleList"
          aria-label="Opties"
          class="absolute inset-y-0 right-0 px-2 cursor-pointer"
          tabindex="-1"
          type="button"
        >
          <Icon
            :class="{ 'rotate-180': hasVisibleList }"
            color="fill-bhr-gray-700"
            name="chevron-down"
            :size="24"
          />
        </button>
      </div>

      <ul
        aria-label="Opties"
        class="bhr-combobox__options"
        ref="optionsListElement"
        role="listbox"
        :class="{ hidden: !hasVisibleList }"
        :id="listId"
      >
        <li
          @click="() => onClick(option)"
          :aria-selected="value === option"
          :class="{
            'bhr-combobox__option--active': isSearchResultActive(index),
          }"
          :id="getOptionIdByIndex(index)"
          class="bhr-combobox__option"
          data-e2e-name="combobox-option"
          role="option"
          v-for="(option, index) in searchResults"
          :key="option"
        >
          {{ option }}
        </li>
      </ul>
    </div>
  </RemovableInput>
</template>
