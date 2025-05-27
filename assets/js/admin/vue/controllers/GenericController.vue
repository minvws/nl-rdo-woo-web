<script setup lang="ts">
import DepartmentLogoManager from '@admin-fe/component/department/DepartmentLogoManager.vue';
import LinkDossiers from '@admin-fe/component/publication/LinkDossiers.vue';
import MarkdownEditor from '@admin-fe/component/form/markdown/MarkdownEditor.vue';
import MultiCombobox from '@admin-fe/component/form/MultiCombobox.vue';
import MultiSelect from '@admin-fe/component/form/MultiSelect.vue';
import MultiText from '@admin-fe/component/form/MultiText.vue';
import PublicationFiles from '@admin-fe/component/publication/file/PublicationFiles.vue';
import PublicationSearchAndGo from '@admin-fe/component/publication/search/PublicationSearchAndGo.vue';
import UploadArea from '@admin-fe/component/file/upload/UploadArea.vue';
import WooDecisionAddDocuments from '@admin-fe/component/publication/woo-decision/AddDocuments.vue';
import WooDecisionUploadDocuments from '@admin-fe/component/publication/woo-decision/UploadDocuments.vue';

const components = {
  DepartmentLogoManager,
  LinkDossiers,
  MarkdownEditor,
  MultiCombobox,
  MultiSelect,
  MultiText,
  PublicationFiles,
  PublicationSearchAndGo,
  UploadArea,
  WooDecisionAddDocuments,
  WooDecisionUploadDocuments,
};

interface Props {
  componentName: keyof typeof components;
  [key: string]: unknown;
}

const { componentName, ...props } = defineProps<Props>();
</script>

<template>
  <component :is="components[componentName]" v-bind="props as any" />
</template>
