<script setup>
import { uniqueId } from '@utils';

const props = defineProps({
  columnResultId: {
    type: String,
    required: true,
  },
  results: {
    type: Array,
    default: () => [],
  },
  title: {
    type: String,
    required: true,
  },
  hideResultId: {
    type: Boolean,
    default: false,
  },
});

const titleId = uniqueId('search-results-title');

const onRowClick = (url) => {
  window.location.assign(url);
};
</script>

<template>
  <template v-if="props.results.length > 0">
    <h2 class="bhr-title mb-2" :id="titleId">{{ props.title }}</h2>

    <table class="bhr-table" :aria-labelledby="titleId">
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
          data-e2e-name="search-previews-result"
        >
          <td class="w-48" v-if="!props.hideResultId">
            <a class="bhr-a" :href="result.link">
              {{ result.id }}
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
