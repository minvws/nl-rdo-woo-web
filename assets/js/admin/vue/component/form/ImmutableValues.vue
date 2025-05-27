<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  values?: string[];
}

const props = withDefaults(defineProps<Props>(), {
  values: () => [],
});

const numberOfValues = computed(() => props.values.length);
</script>

<template>
  <div class="mb-1" v-if="numberOfValues > 0">
    <ul class="list-disc ml-6" v-if="numberOfValues > 1">
      <li class="py-0.5" v-for="value in props.values" :key="value">
        {{ value }}
      </li>
    </ul>

    <p v-else>
      {{ props.values[0] }}
    </p>
  </div>
</template>
