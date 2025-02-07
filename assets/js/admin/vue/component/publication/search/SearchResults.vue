<script setup>
import { computed } from 'vue';
import SearchResultsTable from './SearchResultsTable.vue';

const props = defineProps({
  results: {
    type: Array,
    default: () => [],
  },
});

const filterResultsByType = (results, type) =>
  results.filter((result) => result.type === type);

const documents = computed(() =>
  filterResultsByType(props.results, 'document'),
);
const publications = computed(() =>
  filterResultsByType(props.results, 'dossier'),
);
const mainDocuments = computed(() =>
  filterResultsByType(props.results, 'main_document'),
);
const attachments = computed(() =>
  filterResultsByType(props.results, 'attachment'),
);

const hasDocuments = computed(() => documents.value.length > 0);
const hasPublications = computed(() => publications.value.length > 0);
const hasMainDocuments = computed(() => mainDocuments.value.length > 0);
const hasAttachments = computed(() => attachments.value.length > 0);
const hasResults = computed(() => props.results.length > 0);
</script>

<template>
  <template v-if="hasResults">
    <div v-if="hasPublications" class="mt-6 first:mt-0">
      <SearchResultsTable
        :results="publications"
        columnResultId="Besluitnummer"
        title="Publicaties"
      />
    </div>

    <div v-if="hasDocuments" class="mt-6 first:mt-0">
      <SearchResultsTable
        :results="documents"
        columnResultId="Documentnummer"
        title="Woo-documenten"
      />
    </div>

    <div v-if="hasMainDocuments" class="mt-6 first:mt-0">
      <SearchResultsTable
        :results="mainDocuments"
        columnResultId="Uuid"
        hideResultId="true"
        title="Hoofddocumenten"
      />
    </div>

    <div v-if="hasAttachments" class="mt-6 first:mt-0">
      <SearchResultsTable
        :results="attachments"
        columnResultId="Uuid"
        hideResultId="true"
        title="Bijlagen"
      />
    </div>
  </template>

  <template v-else>
    <p>Geen resultaten gevonden.</p>
  </template>
</template>
