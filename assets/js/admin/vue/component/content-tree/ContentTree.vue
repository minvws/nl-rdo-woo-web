<script setup lang="ts">
import AddButton from '@admin-fe/component/button/AddButton.vue';
import MarkdownEditor from '@admin-fe/component/form/markdown/MarkdownEditor.vue';
import { uniqueId } from '@js/utils';
import { computed, ref, useTemplateRef, watch } from 'vue';
import {
  createContentTreeNode,
  parseContentTree,
  pruneEmptyContentTreeChildren,
} from './content-tree';
import ContentTreeNode from './ContentTreeNode.vue';
import type { ContentTree, ContentTreeChildren } from './interface';

interface Props {
  id: string;
  name: string;
  value: string;
}

const props = defineProps<Props>();

const json = ref<ContentTree>(parseContentTree(props.value));
const children = computed(() => json.value.children ?? []);
const serializedJson = computed(() => {
  const pruned: ContentTree = {
    ...json.value,
    children: pruneEmptyContentTreeChildren(children.value),
  };

  return JSON.stringify(pruned, null, 2);
});
const nextNodeNumber = computed(() => String(children.value.length + 1));

const textarea = useTemplateRef<HTMLTextAreaElement>('textarea');

watch(
  serializedJson,
  () => {
    textarea.value?.dispatchEvent(new Event('input'));
  },
  { flush: 'post' },
);

const updateNode = (index: number, node: ContentTreeChildren) => {
  json.value.children = [...children.value];
  json.value.children[index] = node;
};

const addNode = () => {
  json.value.children = [...children.value, createContentTreeNode()];
};

const removeNode = (index: number) => {
  json.value.children = children.value.filter((_, i) => i !== index);
};

const titleId = uniqueId('content-tree-title');
const introId = uniqueId('content-tree-intro');
const outroId = uniqueId('content-tree-outro');
</script>

<template>
  <div class="bhr-form-row">
    <label class="bhr-label" :for="titleId">Titel</label>
    <p class="bhr-form-help">
      Deze wordt boven de gepubliceerde verhaallijn getoond.
    </p>
    <input
      class="bhr-input-text"
      :id="titleId"
      type="text"
      v-model="json.title"
    />
  </div>

  <div class="bhr-form-row mb-4">
    <label class="bhr-label" :for="introId">Achtergrond</label>
    <p class="bhr-form-help">
      Introductie of context voor bezoekers die het dossier nog niet kennen.
    </p>
    <MarkdownEditor :id="introId" name="" v-model:value="json.intro" />
  </div>

  <p class="bhr-label mb-2">Onderwerpen</p>

  <ContentTreeNode
    :key="index"
    :modelValue="node"
    :number="String(index + 1)"
    @remove="removeNode(index)"
    @update:modelValue="
      (value: ContentTreeChildren) => updateNode(index, value)
    "
    v-for="(node, index) in children"
  />

  <div class="pt-2 mb-4">
    <AddButton @click="addNode"
      >Onderwerp {{ nextNodeNumber }} toevoegen</AddButton
    >
  </div>

  <div class="bhr-form-row">
    <label class="bhr-label" :for="outroId">Conclusie</label>
    <p class="bhr-form-help">
      Afsluitende samenvatting die onderaan de gepubliceerde verhaallijn staat.
    </p>
    <MarkdownEditor :id="outroId" name="" v-model:value="json.outro" />
  </div>

  <textarea
    :id="props.id"
    :name="props.name"
    :value="serializedJson"
    hidden
    readonly
    ref="textarea"
  />
</template>
