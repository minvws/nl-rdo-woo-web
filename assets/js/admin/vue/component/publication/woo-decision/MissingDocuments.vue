<script setup lang="ts">
import { ref, computed } from 'vue';
import Alert from '@admin-fe/component/Alert.vue';
import Icon from '@admin-fe/component/Icon.vue';
import { pluralize } from '@js/utils/string';

interface Props {
  documents: string[];
  expectedCount: number;
  isProcessing: boolean;
}

const props = defineProps<Props>();

const areRemainingDocumentsVisible = ref(false);

const missingCount = computed(() => props.documents.length);
const firstSetOfDocumentsCount = 12;
const remainingDocumentsCount = computed(
  () => props.documents.length - firstSetOfDocumentsCount,
);
const hasToggleButton = computed(
  () => props.documents.length > firstSetOfDocumentsCount,
);
const visibleDocuments = computed(() =>
  areRemainingDocumentsVisible.value
    ? props.documents
    : props.documents.slice(0, firstSetOfDocumentsCount),
);
const toggleButtonText = computed(
  () =>
    `${areRemainingDocumentsVisible.value ? 'Verberg' : 'Toon nog'} ${remainingDocumentsCount.value} ${pluralize('document', 'documenten', remainingDocumentsCount.value)}`,
);
</script>

<template>
  <Alert type="info">
    <template v-if="props.isProcessing" #top>
      <div class="mb-4">
        <Icon name="loader" :size="32" class="animate-spin" />
      </div>
    </template>

    <h3 data-e2e-name="missingDocuments">
      Nog te uploaden:
      <span class="font-bold">{{ missingCount }}</span> van
      {{ props.expectedCount }} document{{
        props.expectedCount !== 1 ? 'en' : ''
      }}.
    </h3>

    <template #extra>
      <output class="block">
        <ul
          class="grid grid-cols-4 gap-x-4"
          data-e2e-name="missing-documents-list"
        >
          <li
            v-for="documentName in visibleDocuments"
            :key="documentName"
            class="py-1"
          >
            <span class="flex">
              <span class="mr-3">
                <Icon name="file-unknown" :size="20" />
              </span>
              <span class="grow truncate pt-0.5">{{ documentName }}</span>
            </span>
          </li>
        </ul>
      </output>

      <button
        v-if="hasToggleButton"
        @click="areRemainingDocumentsVisible = !areRemainingDocumentsVisible"
        type="button"
        class="bhr-a py-2 px-4 mt-2 -ml-4 -mb-2"
      >
        {{ toggleButtonText }}
      </button>
    </template>
  </Alert>
</template>
