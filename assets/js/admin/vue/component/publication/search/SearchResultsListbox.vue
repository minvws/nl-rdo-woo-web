<script setup lang="ts">
import type { PublicationSearchResult } from '@admin-fe/component/publication/search/interface';
import { onOneOfKeysDown } from '@js/utils';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface Props {
  results: PublicationSearchResult[];
}

const props = withDefaults(defineProps<Props>(), {
  results: () => [],
});

const emit = defineEmits<{
  select: [result: PublicationSearchResult];
}>();

let abortController: AbortController | null = null;
const selectedResultId = ref<string | undefined>(undefined);
const hasResults = computed(() => props.results.length > 0);

const pressedArrowDown = () => {
  setNextResultId();
};

const pressedArrowUp = () => {
  setPreviousResultId();
};

const pressedEnter = () => {
  if (!selectedResultId.value) {
    return;
  }

  const selectedResult = props.results.find(
    (result) => result.id === selectedResultId.value,
  );
  if (!selectedResult) {
    return;
  }

  emit('select', selectedResult);
};

const onMouseOver = (resultId?: string) => {
  selectedResultId.value = resultId;
};

const onMouseOut = () => {
  resetSelectedResultId();
};

const resetSelectedResultId = () => {
  selectedResultId.value = undefined;
};

const setNextResultId = () => {
  const nextIndex =
    props.results.findIndex((result) => result.id === selectedResultId.value) +
    1;
  const nextResult = props.results[nextIndex] || props.results[0];
  selectedResultId.value = nextResult.id;
};

const setPreviousResultId = () => {
  const previousIndex =
    props.results.findIndex((result) => result.id === selectedResultId.value) -
    1;
  const previousResult =
    props.results[previousIndex] || props.results[props.results.length - 1];
  selectedResultId.value = previousResult.id;
};

const isSelected = (resultId?: string) => selectedResultId.value === resultId;

const addKeyboardBehaviour = () => {
  removeKeyboardBehaviour();

  abortController = new AbortController();
  const oneOfKeys = [
    'ArrowDown',
    'ArrowUp',
    'End',
    'Enter',
    'Home',
    'PageDown',
    'PageUp',
  ];
  onOneOfKeysDown(
    oneOfKeys,
    (event) => {
      const { key } = event;

      switch (key) {
        case 'ArrowDown':
          pressedArrowDown();
          break;
        case 'ArrowUp':
          pressedArrowUp();
          break;
        case 'Enter':
          pressedEnter();
          break;
        default:
          break;
      }
    },
    { signal: abortController.signal },
  );
};

const removeKeyboardBehaviour = () => {
  abortController?.abort();
  abortController = null;
};

onBeforeUnmount(() => {
  removeKeyboardBehaviour();
});

onMounted(() => {
  addKeyboardBehaviour();
});

watch(() => props.results, resetSelectedResultId);
</script>

<template>
  <ul v-if="hasResults">
    <li
      v-for="result in props.results"
      @click="emit('select', result)"
      @pointerover="() => onMouseOver(result.id)"
      @pointerout="onMouseOut"
      :key="result.id"
      :aria-selected="isSelected(result.id)"
      class="py-2 px-3 border-b border-bhr-platinum bhr-clickable-row"
      :class="{ 'bhr-outline': isSelected(result.id) }"
      role="option"
    >
      {{ result.title }}
    </li>
  </ul>
</template>
