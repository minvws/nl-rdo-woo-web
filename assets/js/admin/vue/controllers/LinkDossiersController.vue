<script setup>
import LinkDossiers from '@admin-fe/component/publication/LinkDossiers.vue';

const props = defineProps(['endpoint', 'name', 'submitErrors']);
</script>

<template>
  <LinkDossiers v-bind="props" />
</template>
