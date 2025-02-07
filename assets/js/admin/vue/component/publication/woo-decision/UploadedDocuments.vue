<script setup lang="ts">
import Icon from '@admin-fe/component/Icon.vue';
import MimeTypeIcon from '@admin-fe/component/file/MimeTypeIcon.vue';
import { computed } from 'vue';
import { UploadedWooDecisionDocumentFile } from './interface';

interface Props {
  files?: UploadedWooDecisionDocumentFile[];
}

const props = withDefaults(defineProps<Props>(), {
  files: () => [],
});

const numberOfFiles = computed(() => props.files.length);
</script>

<template>
  <div
    class="bg-bhr-cornsilk p-4 mb-4"
    v-if="numberOfFiles > 0"
    data-e2e-name="uploaded-files"
  >
    <h3 class="font-bold">Geüploade bestanden</h3>

    <ul
      class="bg-bhr-cornsilk max-h-28 overflow-y-auto"
      :class="{
        'grid grid-cols-3 gap-x-4': numberOfFiles >= 3,
        'grid grid-cols-2 gap-x-4': numberOfFiles === 2,
      }"
    >
      <li v-for="file in props.files" :key="file.id" class="pt-2">
        <div class="flex items-center">
          <div class="flex grow items-center truncate mr-2">
            <span class="mr-2"
              ><MimeTypeIcon :mime-type="file.mimeType" :size="20"
            /></span>
            <div class="leading-none truncate">
              {{ file.name }}
            </div>
          </div>

          <div>
            <Icon
              color="fill-bhr-philippine-green"
              name="check-rounded-filled"
              :size="16"
            />
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>
