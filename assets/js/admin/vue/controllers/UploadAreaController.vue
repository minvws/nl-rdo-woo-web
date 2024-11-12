<script setup>
import UploadArea from '@admin-fe/component/file/upload/UploadArea.vue';

const props = defineProps([
  'allowedMimeTypes',
  'allowMultiple',
  'enableAutoUpload',
  'groupId',
  'id',
  'maxFileSize',
  'name',
  'tip',
]);
</script>

<template>
  <UploadArea v-bind="props" />
</template>
