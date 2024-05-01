<script setup>
  import InputErrors from './InputErrors.vue';
  import Icon from '@admin-fe/component/Icon.vue';
  import { useInputAriaDescribedBy, useInputStore } from '@admin-fe/composables';
  import { validators } from '@admin-fe/form';
  import { removeAccents, uniqueId } from '@js/utils';
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
    value: {
      type: String,
      required: false,
      default: '',
    },
    errors: {
      type: Array,
      default: () => [],
    },
  });

  const activeSearchResultIndex = ref(-1);
  const errors = ref([]);
  const inputElement = ref(null);
  const inputId = `${uniqueId('input')}`;
  const isListVisible = ref(false);
  const listId = `${uniqueId('list')}`;
  const wrapperElement = ref(null);
  const value = ref(props.value);

  const onDelete = () => {
    emit('delete', inputStore);
  }

  const onUpdate = () => {
    emit('update', value.value);

    activeSearchResultIndex.value = -1;
  }

  const hideList = () => isListVisible.value = false;
  const showList = () => isListVisible.value = true;
  const toggleList = () => isListVisible.value = !isListVisible.value;

  const hasSearchResults = computed(() => searchResults.value.length > 0);
  const hasVisibleList = computed(() => isListVisible.value && hasSearchResults.value);

  const searchResults = computed(() => {
    if (!props.value) {
      return props.options;
    }

    return props.options.filter((option) => {
      return removeAccents(option.toLowerCase()).includes(removeAccents(props.value.toLowerCase()));
    });
  });

  const increaseActiveSearchResultIndex = () => {
    if (activeSearchResultIndex.value === searchResults.value.length - 1) {
      activeSearchResultIndex.value = 0;
      return;
    }

    activeSearchResultIndex.value += 1;
  }

  const decreaseActiveSearchResultIndex = () => {
    if (activeSearchResultIndex.value < 1) {
      activeSearchResultIndex.value = searchResults.value.length - 1;
      return;
    }

    activeSearchResultIndex.value -= 1;
  }

  const onClick = (option) => {
    setValue(option);
    inputElement.value?.focus();
  }

  const setValue = (inputValue) => {
    value.value = inputValue;
    onUpdate();

    hideList();
  }

  const onAltPlusArrowDown = () => {
    showList();
  }

  const onArrowDown = () => {
    showList();

    increaseActiveSearchResultIndex();
  }

  const onArrowUp = () => {
    showList();

    decreaseActiveSearchResultIndex();
  }

  const onClickOnInputWrapper = () => {
    toggleList();
  }

  const onEnter = () => {
    if (activeSearchResultIndex.value === -1) {
      hideList();
      return;
    }

    const inputValue = searchResults.value[activeSearchResultIndex.value];
    setValue(inputValue);
  }

  const onEscape = () => {
    if (isListVisible.value) {
      hideList();
      return;
    }

    value.value = '';
    onUpdate();
  }

  const getOptionIdByIndex = (index) => `${listId}-${index}`;
  const getActiveDescendant = () => {
    if (activeSearchResultIndex.value === -1) {
      return null;
    }

    scrollSearchResultIntoView(activeSearchResultIndex.value);
    return getOptionIdByIndex(activeSearchResultIndex.value);
  }

  const scrollSearchResultIntoView = (index) => {
    if (index === -1) {
      return;
    }

    const optionElement = wrapperElement.value.querySelector(`#${getOptionIdByIndex(index)}`);
    optionElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  const isSearchResultActive = (index) => index === activeSearchResultIndex.value;

  const onBlur = () => {
    inputStore.markAsTouched();

    setTimeout(() => {
      hideList();
    }, 200);
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
      showList();
    }
    emit('mounted', inputStore);
  });
</script>

<template>
  <div class="bhr-combobox" ref="wrapperElement">
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
      <div class="relative grow">
        <div class="bhr-combobox__input grow" @click="onClickOnInputWrapper">
          <input
            @blur="onBlur"
            @focus="onFocus"
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
            ref="inputElement"
            role="combobox"
            type="text"
            v-model="value"
          >
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
              color="fill-bhr-dim-gray"
              name="chevron-down"
              size="24"
            />
          </button>
        </div>

        <ul
          class="bhr-combobox__options"
          :class="{ 'hidden': !hasVisibleList }"
          :id="listId"
          role="listbox"
          aria-label="Opties"
        >
          <li
            @click="() => onClick(option)"
            :class="{ 'bhr-combobox__option--active': isSearchResultActive(index) }"
            :id="getOptionIdByIndex(index)"
            class="bhr-combobox__option"
            role="option"
            v-for="(option, index) in searchResults"
            :key="option"
          >
            {{ option }}
          </li>
        </ul>
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
