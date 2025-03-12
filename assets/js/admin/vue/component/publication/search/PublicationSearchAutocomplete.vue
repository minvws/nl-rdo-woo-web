<script setup lang="ts">
import { ref } from 'vue';
import type { PublicationSearchResult } from './interface';
import PublicationSearchForm from './PublicationSearchForm.vue';
import PublicationSearchInput from './PublicationSearchInput.vue';
import SearchResultsListbox from './SearchResultsListbox.vue';
import {
  PUBLICATION_SEARCH_INPUT_ID,
  PUBLICATION_SEARCH_RESULTS_ID,
} from './static';

interface Props {
  label: string;
  publicationType?: string;
  resultType?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  select: [result: PublicationSearchResult];
}>();

const publicationSearchInput =
  ref<InstanceType<typeof PublicationSearchInput>>();
const areResultsVisible = ref(false);
const searchResults = ref<PublicationSearchResult[]>([]);

const onFormEscape = () => {
  publicationSearchInput.value?.setFocus();
  hideSearchResults();
};

const onFormFocusOut = () => {
  hideSearchResults();
};

const hideSearchResults = () => {
  areResultsVisible.value = false;
};

const showSearchResults = () => {
  areResultsVisible.value = true;
};

const onResultsUpdated = (results: PublicationSearchResult[]) => {
  searchResults.value = results;
};

const onSelect = (result: PublicationSearchResult) => {
  publicationSearchInput.value?.setValue(result.title);
  hideSearchResults();

  emit('select', result);
};

defineExpose({
  reset: () => {
    publicationSearchInput.value?.setValue('');
    hideSearchResults();
    searchResults.value = [];
  },
});
</script>

<template>
  <PublicationSearchForm @escape="onFormEscape" @focusOut="onFormFocusOut">
    <label class="bhr-label mt-4" :for="PUBLICATION_SEARCH_INPUT_ID">{{
      props.label
    }}</label>

    <PublicationSearchInput
      ref="publicationSearchInput"
      @resultsUpdated="onResultsUpdated"
      @hideResults="hideSearchResults"
      @showResults="showSearchResults"
      ariaHaspopup="listbox"
      class="rounded-none"
      :id="PUBLICATION_SEARCH_INPUT_ID"
      placeholder="Zoeken op dossiernummer"
      :is-expanded="areResultsVisible"
      :publication-type="props.publicationType"
      :result-type="props.resultType"
    />

    <div
      :class="{ hidden: !areResultsVisible }"
      class="mt-2"
      data-e2e-name="search-previews-results"
      :id="PUBLICATION_SEARCH_RESULTS_ID"
      role="listbox"
      tabindex="-1"
    >
      <SearchResultsListbox
        v-if="areResultsVisible"
        @select="onSelect"
        :results="searchResults"
      />
    </div>
  </PublicationSearchForm>
</template>
