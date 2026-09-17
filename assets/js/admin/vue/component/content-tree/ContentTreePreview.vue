<script setup lang="ts">
import { nextTick, onMounted, onUnmounted, ref } from 'vue';
import { md } from '@admin-fe/component/form/markdown/markdownit';
import {
  createContentTree,
  isEmptyContentTree,
  parseContentTree,
  pruneEmptyContentTreeChildren,
} from './content-tree';
import ContentTreePreviewNode from './ContentTreePreviewNode.vue';
import type { ContentTree } from './interface';

interface Props {
  treeId: string;
}

const props = defineProps<Props>();

const tree = ref<ContentTree>(createContentTree());

type FieldElement = HTMLInputElement | HTMLTextAreaElement;

const getField = (id: string) =>
  document.getElementById(id) as FieldElement | null;

const readTree = () => {
  const parsed = parseContentTree(getField(props.treeId)?.value ?? '');
  tree.value = {
    ...parsed,
    children: pruneEmptyContentTreeChildren(parsed.children ?? []),
  };
};

onMounted(async () => {
  // The field may be rendered by its own Vue component and not be present
  // in the DOM yet on this component's own mount.
  await nextTick();

  readTree();
  getField(props.treeId)?.addEventListener('input', readTree);
});

onUnmounted(() => {
  getField(props.treeId)?.removeEventListener('input', readTree);
});
</script>

<template>
  <div>
    <h2 class="bhr-title-sm mb-1" v-if="tree.title">{{ tree.title }}</h2>
    <div
      class="bhr-content mb-4"
      v-html="md.render(tree.intro)"
      v-if="tree.intro"
    />

    <ContentTreePreviewNode
      :key="index"
      :node="node"
      :number="String(index + 1)"
      v-for="(node, index) in tree.children ?? []"
    />

    <div
      class="bhr-content mt-4"
      v-html="md.render(tree.outro)"
      v-if="tree.outro"
    />

    <p class="bhr-text-muted" v-if="isEmptyContentTree(tree)">
      Nog geen inhoud om te tonen.
    </p>
  </div>
</template>
