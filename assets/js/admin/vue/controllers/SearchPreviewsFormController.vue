<script setup>
import SearchPreviewsForm from '@admin-fe/component/publication/search/SearchPreviewsForm.vue';

const props = defineProps(['endpoint', 'id', 'label']);
</script>

<template>
  <SearchPreviewsForm v-bind="props" />
</template>
