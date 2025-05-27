<script setup lang="ts">
import { uniqueId } from '@utils';
import type { PublicationSearchResult } from './interface';

interface Props {
  columnResultId: string;
  hideResultId?: boolean;
  results: PublicationSearchResult[];
  title: string;
}

const props = withDefaults(defineProps<Props>(), {
  hideResultId: false,
  results: () => [],
});

const titleId = uniqueId('search-results-title');

const onRowClick = (url: string) => {
  window.location.assign(url);
};
</script>

<template>
  <template v-if="props.results.length > 0">
    <h2 class="bhr-title mb-2" :id="titleId">{{ props.title }}</h2>

    <table
      class="bhr-table"
      :aria-labelledby="titleId"
      :data-e2e-name="`table-${props.results[0].type}`"
    >
      <thead class="sr-only">
        <tr>
          <th v-if="!props.hideResultId" scope="col">
            {{ props.columnResultId }}
          </th>
          <th scope="col">Titel</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="result in results"
          @click="onRowClick(result.link)"
          :key="result.id"
          class="bhr-clickable-row"
        >
          <td class="w-48" v-if="!props.hideResultId">
            <a class="bhr-a" :href="result.link">
              {{ result.number ?? result.id }}
            </a>
          </td>
          <td>
            <a v-if="props.hideResultId" class="bhr-a" :href="result.link">
              {{ result.title }}
            </a>
            <span v-else>{{ result.title }}</span>
          </td>
        </tr>
      </tbody>
    </table>
  </template>
</template>
