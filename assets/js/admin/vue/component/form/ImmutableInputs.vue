<script setup lang="ts">
import { createName } from '@admin-fe/form';

interface Props {
  name: string;
  values?: string[];
}

const props = withDefaults(defineProps<Props>(), {
  values: () => [],
});
</script>

<template>
  <input
    v-for="(value, index) in props.values"
    hidden
    :key="createName(props.name, index)"
    :name="createName(props.name, index)"
    :value="value"
    type="hidden"
  />
</template>
