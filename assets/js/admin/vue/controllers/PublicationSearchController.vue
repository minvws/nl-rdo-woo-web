<script setup>
import PublicationSearchAndGo from '@admin-fe/component/publication/search/PublicationSearchAndGo.vue';

const props = defineProps(['endpoint', 'label']);
</script>

<template>
  <PublicationSearchAndGo v-bind="props" />
</template>
