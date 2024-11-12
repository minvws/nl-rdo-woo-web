<script setup>
import Icon from '../../Icon.vue';
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
          <th scope="col">Meer</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="result in results"
          :key="result.id"
          class="bhr-clickable-row"
          data-e2e-name="search-previews-result"
        >
          <td class="w-48" v-if="!props.hideResultId">
            <a class="bhr-fill-cell" :href="result.link">
              {{ result.id }}
            </a>
          </td>
          <td>
            <a class="bhr-fill-cell" :href="result.link">{{ result.title }}</a>
          </td>
          <td class="text-right w-10">
            <a class="bhr-fill-cell" :href="result.link">
              <span class="sr-only">Details {{ result.id }}</span>
              <Icon name="chevron-right" />
            </a>
          </td>
        </tr>
      </tbody>
    </table>
  </template>
</template>
