<script setup lang="ts">
import { validateResponse } from '@js/admin/utils';
import { debounce, getCurrentOrigin } from '@utils';
import { computed, ref } from 'vue';
import Icon from '../../Icon.vue';
import {
  type PublicationSearchResult,
  publicationSearchResultsSchema,
} from '../interface';
import { PUBLICATION_SEARCH_RESULTS_ID } from './static';

interface Props {
  ariaAutocomplete?: 'list';
  ariaHaspopup: 'dialog' | 'listbox';
  class?: string;
  dossierId?: string;
  id: string;
  isExpanded: boolean;
  placeholder?: string;
  publicationType?: string;
  resultType?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  hideResults: [];
  showResults: [];
  resultsUpdated: [results: PublicationSearchResult[]];
}>();

const inputElement = ref<HTMLInputElement>();

const query = ref('');
const queryLength = computed(() => query.value.length);
const hasValidSearchQuery = computed(() => queryLength.value >= 3);
const isResetQueryIconVisible = computed(() => queryLength.value > 0);
const queryResults = ref<PublicationSearchResult[]>([]);

const handleInput = async () => {
  if (!hasValidSearchQuery.value) {
    emptySearchResults();
    hideResults();
    return;
  }

  const response = await validateResponse(
    fetch(createEndpoint(query.value)),
    publicationSearchResultsSchema,
  );
  queryResults.value = response;
  emit('resultsUpdated', queryResults.value);
  showResults();
};

const createEndpoint = (query: string) => {
  const endpoint = new URL('/balie/api/publication/search', getCurrentOrigin());
  endpoint.searchParams.set('q', query);
  if (props.dossierId) {
    endpoint.searchParams.set('dossierId', props.dossierId);
  }
  if (props.publicationType) {
    endpoint.searchParams.set('filter[publicationType]', props.publicationType);
  }
  if (props.resultType) {
    endpoint.searchParams.set('filter[resultType]', props.resultType);
  }
  return endpoint.toString();
};

const debouncedHandleInput = debounce(handleInput, 250);

const onInputFocus = () => {
  if (!hasValidSearchQuery.value) {
    return;
  }

  showResults();
};

const resetQuery = () => {
  emptySearchResults();
  hideResults();
  setValue('');
  setFocus();
};

const emptySearchResults = () => {
  queryResults.value = [];
  emit('resultsUpdated', queryResults.value);
};

const hideResults = () => {
  emit('hideResults');
};

const showResults = () => {
  emit('showResults');
};

const setFocus = () => {
  inputElement.value?.focus();
};

const setValue = (value: string) => {
  query.value = value;
};

defineExpose({
  setFocus,
  setValue,
});
</script>

<template>
  <div class="relative">
    <input
      @focus="onInputFocus"
      @input="debouncedHandleInput"
      :aria-autocomplete="props.ariaAutocomplete"
      :aria-controls="PUBLICATION_SEARCH_RESULTS_ID"
      :aria-expanded="props.isExpanded"
      :aria-haspopup="props.ariaHaspopup"
      autocomplete="off"
      class="bhr-input-text bhr-input-text--with-icon-after text-base"
      :class="props.class"
      :id="props.id"
      name="query"
      ref="inputElement"
      :placeholder="props.placeholder"
      role="combobox"
      type="text"
      v-model="query"
      data-e2e-name="dossier-search-results"
    />

    <button
      v-if="isResetQueryIconVisible"
      class="bhr-input-icon bhr-input-icon--after cursor-pointer"
      type="button"
      @click="resetQuery"
    >
      <Icon name="cross" color="fill-bhr-spanish-gray" :size="20" />
      <span class="sr-only">Wis tekstinvoer</span>
    </button>
    <span
      v-else
      class="bhr-input-icon bhr-input-icon--after pointer-events-none"
    >
      <Icon name="magnifier" :size="20" />
      <span class="sr-only">Zoeken</span>
    </span>
  </div>
</template>
