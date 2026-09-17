<script setup lang="ts">
import { uniqueId } from '@js/utils';
import { computed } from 'vue';
import AddButton from '@admin-fe/component/button/AddButton.vue';
import MarkdownEditor from '@admin-fe/component/form/markdown/MarkdownEditor.vue';
import Icon from '@admin-fe/component/Icon.vue';
import { createContentTreeNode } from './content-tree';
import type { ContentTreeChildren } from './interface';

interface Props {
  // The position of this node within the tree, as "1", "1.2", "1.2.1", ...
  number: string;
}

const props = defineProps<Props>();

const node = defineModel<ContentTreeChildren>({ required: true });

const emit = defineEmits<{ remove: [] }>();

const titleId = uniqueId('content-tree-title');
const bodyId = uniqueId('content-tree-body');

const level = computed(() => props.number.split('.').length);
// The tree starts at <h2>, so every level below it gets the next heading level,
// down to the deepest heading html offers.
const headingTag = computed(() => `h${Math.min(level.value + 1, 6)}`);
const children = computed(() => node.value.children ?? []);
// The number the next child will get once it is added.
const nextChildNumber = computed(
  () => `${props.number}.${children.value.length + 1}`,
);

const updateChild = (index: number, child: ContentTreeChildren) => {
  if (node.value.children) {
    node.value.children[index] = child;
  }
};

const addChild = () => {
  node.value.children = [...children.value, createContentTreeNode()];
};

const removeChild = (index: number) => {
  node.value.children?.splice(index, 1);
};
</script>

<template>
  <div
    class="border-l-4 border-bhr-gray-400 pl-4 py-2 mb-4"
    :data-level="level"
  >
    <div class="flex pb-4">
      <component class="font-bold bhr-text-muted grow" :is="headingTag"
        >Onderwerp {{ props.number }}</component
      >
      <button
        @click="emit('remove')"
        class="bhr-btn-ghost-danger"
        type="button"
      >
        <Icon color="fill-current" :size="20" name="trash-bin" />
        <span class="sr-only">Onderwerp {{ props.number }} verwijderen</span>
      </button>
    </div>

    <div class="bhr-form-row">
      <label class="bhr-label" :for="titleId"
        >Titel<span class="sr-only">({{ props.number }})</span></label
      >
      <input
        class="bhr-input-text"
        :id="titleId"
        type="text"
        v-model="node.title"
      />
    </div>

    <div class="bhr-form-row mb-4">
      <label class="bhr-label" :for="bodyId"
        >Omschrijving <span class="sr-only">({{ props.number }})</span></label
      >
      <MarkdownEditor :id="bodyId" name="" v-model:value="node.body" />
    </div>

    <ContentTreeNode
      :key="index"
      :number="`${props.number}.${index + 1}`"
      :modelValue="child"
      @remove="removeChild(index)"
      @update:modelValue="
        (value: ContentTreeChildren) => updateChild(index, value)
      "
      v-for="(child, index) in children"
    />

    <AddButton @click="addChild"
      >Onderwerp {{ nextChildNumber }} toevoegen</AddButton
    >
  </div>
</template>
