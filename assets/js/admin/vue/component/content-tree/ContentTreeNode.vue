<script setup lang="ts">
import AddButton from '@admin-fe/component/button/AddButton.vue';
import Collapsible from '@admin-fe/component/Collapsible.vue';
import MarkdownEditor from '@admin-fe/component/form/markdown/MarkdownEditor.vue';
import Icon from '@admin-fe/component/Icon.vue';
import { uniqueId } from '@js/utils';
import { computed, nextTick, ref, useTemplateRef } from 'vue';
import {
  contentTreeNodeClass,
  createContentTreeNode,
  focusAfterRemoval,
  isEmptyContentTreeNode,
  moveItem,
} from './content-tree';
import type { ContentTreeChildren } from './interface';

interface Props {
  canSort?: boolean;
  // The position of this node within the tree, as "1", "1.2", "1.2.1", ...
  number: string;
}

const props = withDefaults(defineProps<Props>(), {
  canSort: false,
});

const node = defineModel<ContentTreeChildren>({ required: true });

const emit = defineEmits<{ remove: []; move: [direction: 'up' | 'down'] }>();

const titleId = uniqueId('content-tree-title');
const bodyId = uniqueId('content-tree-body');
const collapseId = uniqueId('content-tree-collapse');

const isCollapsed = ref(!isEmptyContentTreeNode(node.value));

const level = computed(() => props.number.split('.').length);
// The tree starts at <h2>, so every level below it gets the next heading level,
// down to the deepest heading html offers.
const headingTag = computed(() => `h${Math.min(level.value + 1, 6)}`);
const label = computed(() => (level.value === 1 ? 'Onderwerp' : 'Onderdeel'));
const titleLabel = computed(() => node.value.title || 'zonder titel');
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

const root = useTemplateRef<HTMLElement>('root');
const addChildButton =
  useTemplateRef<InstanceType<typeof AddButton>>('addChildButton');

const removeChild = async (index: number) => {
  node.value.children?.splice(index, 1);
  await nextTick();
  focusAfterRemoval(
    root.value,
    `${props.number}.`,
    index,
    children.value.length,
    addChildButton.value?.$el ?? null,
  );
};

const moveChild = (index: number, direction: 'up' | 'down') => {
  if (node.value.children) {
    node.value.children = moveItem(node.value.children, index, direction);
  }
};
</script>

<template>
  <div
    ref="root"
    class="border-l-4 border-bhr-gray-400 pl-4 py-2 mb-4"
    :class="contentTreeNodeClass(props.number)"
    :data-level="level"
    :data-e2e-name="`content-tree-node-${props.number}`"
  >
    <div class="flex justify-between">
      <component class="grow" :is="headingTag">
        <button
          :aria-expanded="!isCollapsed"
          :aria-controls="collapseId"
          class="flex font-bold bhr-text-muted mr-2"
          data-e2e-name="content-tree-node-toggle-collapse"
          type="button"
          @click="() => (isCollapsed = !isCollapsed)"
        >
          <Icon
            class="transition-transform mr-2"
            :class="{ '-rotate-90': isCollapsed }"
            color="fill-current"
            :size="24"
            name="chevron-down"
          />
          {{ label }} {{ props.number }}
          <span class="font-normal pl-2 bhr-text-muted">
            ({{ titleLabel }})
          </span>
        </button>
      </component>

      <div class="flex">
        <div
          v-if="props.canSort"
          class="border border-bhr-gray-500 rounded-md mr-4"
        >
          <button
            class="px-1"
            data-e2e-name="content-tree-node-move-down"
            type="button"
            @click="emit('move', 'down')"
          >
            <Icon color="fill-bhr-gray-700" :size="16" name="arrow-down" />
            <span class="sr-only"
              >{{ label }} {{ props.number }} omlaag verplaatsen</span
            >
          </button>
          <button
            class="px-1 border-l border-bhr-gray-500"
            data-e2e-name="content-tree-node-move-up"
            type="button"
            @click="emit('move', 'up')"
          >
            <Icon color="fill-bhr-gray-700" :size="16" name="arrow-up" />
            <span class="sr-only"
              >{{ label }} {{ props.number }} omhoog verplaatsen</span
            >
          </button>
        </div>

        <button
          @click="emit('remove')"
          class="bhr-btn-ghost-danger js-content-tree-node-remove"
          data-e2e-name="content-tree-node-remove"
          type="button"
        >
          <Icon color="fill-current" :size="20" name="trash-bin" />
          <span class="sr-only"
            >{{ label }} {{ props.number }} verwijderen</span
          >
        </button>
      </div>
    </div>

    <Collapsible :id="collapseId" v-model="isCollapsed">
      <div class="bhr-form-row pt-2" data-e2e-name="content-tree-node-title">
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

      <div class="bhr-form-row mb-4" data-e2e-name="content-tree-node-body">
        <label class="bhr-label" :for="bodyId"
          >Omschrijving <span class="sr-only">({{ props.number }})</span></label
        >
        <MarkdownEditor :id="bodyId" name="" v-model:value="node.body" />
      </div>

      <ContentTreeNode
        :canSort="children.length > 1"
        :key="index"
        :number="`${props.number}.${index + 1}`"
        :modelValue="child"
        @remove="removeChild(index)"
        @move="moveChild(index, $event)"
        @update:modelValue="
          (value: ContentTreeChildren) => updateChild(index, value)
        "
        v-for="(child, index) in children"
      />

      <AddButton
        data-e2e-name="content-tree-node-add-child"
        ref="addChildButton"
        @click="addChild"
        >Onderdeel {{ nextChildNumber }} toevoegen</AddButton
      >
    </Collapsible>
  </div>
</template>
