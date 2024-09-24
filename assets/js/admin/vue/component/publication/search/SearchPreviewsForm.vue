<script setup>
  import { debounce, onFocusOut, onKeyDown } from '@utils';
  import { computed, onBeforeUnmount, ref } from 'vue';
  import Icon from '../../Icon.vue';
  import SearchResults from './SearchResults.vue';

  const MINIMAL_QUERY_LENGTH = 3;

  const props = defineProps({
    endpoint: {
      type: String,
      required: true,
    },
    id: {
      type: String,
    },
    label: {
      type: String,
      required: true,
    },
  });

  let abortController;

  const areResultsVisible = ref(false);
  const formElement = ref(null);
  const inputElement = ref(null);
  const query = ref('');
  const results = ref([]);

  const hasValidSearchQuery = computed(() => query.value.length >= MINIMAL_QUERY_LENGTH);
  const isResetQueryIconVisible = computed(() => query.value.length > 0);

  const handleInput = async () => {
    if (query.value.length < MINIMAL_QUERY_LENGTH) {
      emptySearchResults();
      hideResults();
      return;
    }

    const response = await fetch(`${props.endpoint}?q=${query.value}`);
    results.value = await response.json();
    showResults();
  }

  const emptySearchResults = () => {
    results.value = [];
  }

  const debouncedHandleInput = debounce(handleInput, 250);

  const resetQuery = () => {
    emptySearchResults();
    hideResults();
    query.value = '';
    inputElement.value.focus();
  }

  const hideResults = () => {
    areResultsVisible.value = false;
    unObserve();
  }

  const onInputFocus = () => {
    if (!hasValidSearchQuery.value) {
      return;
    }

    showResults();
  }

  const showResults = () => {
    areResultsVisible.value = true;
    observe();
  }

  const observe = () => {
    unObserve();

    abortController = new AbortController();

    document.addEventListener('click', (event) => {
      const { target } = event;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      if (!formElement.value.contains(target)) {
        hideResults();
      }
    }, { signal: abortController.signal });

    onFocusOut(formElement.value, () => {
      hideResults();
    }, { signal: abortController.signal });

    onKeyDown('Escape', () => {
      inputElement.value.focus();
      hideResults();
    }, { signal: abortController.signal });
  }

  const unObserve = () => {
    abortController?.abort();
  }

  onBeforeUnmount(() => {
    unObserve();
  });
</script>

<template>
  <form @submit.prevent="() => {}" class="relative" :id="props.id" method="get" ref="formElement">
    <slot>
      <div class="fixed inset-0 pointer-events-none bg-black/25" :class="{ hidden: !areResultsVisible }"></div>
      <div class="flex items-center">
        <label class="block text-bhr-dim-gray mr-3 leading-tight" for="search-previews">
          {{ props.label }}
        </label>
        <div class="relative">
          <input
            @focus="onInputFocus"
            @input="debouncedHandleInput"
            aria-controls="search-previews-results"
            :aria-expanded="areResultsVisible"
            aria-haspopup="dialog"
            autocomplete="off"
            class="bhr-input-text bhr-input-text--with-icon-after text-base w-60 peer"
            id="search-previews"
            name="query"
            ref="inputElement"
            role="combobox"
            type="text"
            v-model="query"
          />
          <button v-if="isResetQueryIconVisible" class="bhr-input-icon bhr-input-icon--after cursor-pointer" type="button" @click="resetQuery">
            <Icon name="cross" color="fill-bhr-spanish-gray" :size="20" />
            <span class="sr-only">Wis tekstinvoer</span>
          </button>
          <span v-else class="bhr-input-icon bhr-input-icon--after pointer-events-none">
            <Icon name="magnifier" :size="20" />
            <span class="sr-only">Zoeken</span>
          </span>
        </div>
      </div>

      <div
        :class="{ hidden: !areResultsVisible }"
        class="bhr-overlay-card right-0 mt-2 w-[56rem]"
        data-e2e-name="search-previews-results"
        id="search-previews-results"
        role="dialog"
      >
        <SearchResults :results="results" />
      </div>
    </slot>
  </form>
</template>
