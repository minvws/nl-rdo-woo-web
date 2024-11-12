<script setup lang="ts">
import PublicationSearchForm from './PublicationSearchForm.vue';
import PublicationSearchInput from './PublicationSearchInput.vue';
import SearchResults from './SearchResults.vue';
import { ref } from 'vue';
import {
  PUBLICATION_SEARCH_INPUT_ID,
  PUBLICATION_SEARCH_RESULTS_ID,
} from './static';

interface Props {
  endpoint: string;
  label: string;
}

const props = defineProps<Props>();

const publicationSearchInput =
  ref<InstanceType<typeof PublicationSearchInput>>();
const areResultsVisible = ref(false);
const searchResults = ref<object[]>([]);

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

const onResultsUpdated = (results: object[]) => {
  searchResults.value = results;
};
</script>

<template>
  <PublicationSearchForm @escape="onFormEscape" @focusOut="onFormFocusOut">
    <div
      class="fixed inset-0 pointer-events-none bg-black/25"
      :class="{ hidden: !areResultsVisible }"
    ></div>
    <div class="flex items-center">
      <label
        class="block text-bhr-dim-gray mr-3 leading-tight"
        :for="PUBLICATION_SEARCH_INPUT_ID"
      >
        {{ props.label }}
      </label>

      <PublicationSearchInput
        ref="publicationSearchInput"
        @resultsUpdated="onResultsUpdated"
        @hideResults="hideSearchResults"
        @showResults="showSearchResults"
        ariaHaspopup="dialog"
        class="w-60 peer"
        :endpoint="props.endpoint"
        :id="PUBLICATION_SEARCH_INPUT_ID"
        :is-expanded="areResultsVisible"
      />
    </div>

    <div
      :class="{ hidden: !areResultsVisible }"
      class="bhr-overlay-card right-0 mt-2 w-[56rem]"
      data-e2e-name="search-previews-results"
      :id="PUBLICATION_SEARCH_RESULTS_ID"
      role="dialog"
      tabindex="-1"
    >
      <SearchResults :results="searchResults" />
    </div>
  </PublicationSearchForm>
</template>
