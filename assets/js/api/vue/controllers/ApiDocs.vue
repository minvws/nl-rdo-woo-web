<script setup lang="ts">
import { ApiReference } from '@scalar/api-reference';
import '@scalar/api-reference/style.css';

interface Props {
  openApiUrl: string;
}

const props = defineProps<Props>();

const configuration = {
  baseServerURL: window.location.origin,
  url: props.openApiUrl,
  hideClientButton: true,
};
</script>

<template>
  <ApiReference :configuration="configuration" />
</template>
