<script setup lang="ts">
import { computed } from 'vue';
import { md } from '@admin-fe/component/form/markdown/markdownit';
import type { ContentTreeChildren } from './interface';

interface Props {
  node: ContentTreeChildren;
  number: string;
}

const props = defineProps<Props>();

const level = computed(() => props.number.split('.').length);
const headingTag = computed(() => `h${Math.min(level.value + 1, 6)}`);
const children = computed(() => props.node.children ?? []);
</script>

<template>
  <div class="border-l border-bhr-gray-400 pl-2.5 py-2 mb-2">
    <component :is="headingTag" class="bhr-bold">{{ node.title }}</component>
    <div class="bhr-content" v-html="md.render(node.body)" />

    <div :class="{ 'pt-2': children.length > 0 }">
      <ContentTreePreviewNode
        :key="index"
        :node="child"
        :number="`${props.number}.${index + 1}`"
        v-for="(child, index) in children"
      />
    </div>
  </div>
</template>
