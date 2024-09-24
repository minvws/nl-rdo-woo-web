<script setup>
  import { computed } from 'vue';
  import SearchResultsTable from './SearchResultsTable.vue';

  const props = defineProps({
    results: {
      type: Array,
      default: () => [],
    },
  });

  const filterResultsByType = (results, type) => results.filter((result) => result.type === type);

  const documents = computed(() => filterResultsByType(props.results, 'document'));
  const publications = computed(() => filterResultsByType(props.results, 'dossier'));

  const hasDocuments = computed(() => documents.value.length > 0);
  const hasPublications = computed(() => publications.value.length > 0);
  const hasResults = computed(() => props.results.length > 0);
</script>

<template>
  <template v-if="hasResults">
    <SearchResultsTable
      :results="publications"
      columnResultId="Besluitnummer"
      title="Publicaties"
    />

    <div v-if="hasDocuments" :class="{ 'mt-6': hasPublications }">
      <SearchResultsTable
        :results="documents"
        columnResultId="Documentnummer"
        title="Documenten"
      />
    </div>
  </template>

  <template v-else>
    <p>Geen resultaten gevonden.</p>
  </template>
</template>
