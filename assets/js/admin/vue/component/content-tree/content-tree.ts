import type { ContentTree, ContentTreeChildren } from './interface';

export const createContentTree = (): ContentTree => ({
  title: '',
  intro: '',
  children: [],
  outro: '',
});

export const createContentTreeNode = (): ContentTreeChildren => ({
  title: '',
  body: '',
  children: [],
});

export const parseContentTree = (value: string): ContentTree => {
  try {
    const parsed: unknown = JSON.parse(value);
    return parsed && typeof parsed === 'object' && !Array.isArray(parsed)
      ? { ...createContentTree(), ...parsed }
      : createContentTree();
  } catch {
    return createContentTree();
  }
};

export const isEmptyContentTreeNode = (node: ContentTreeChildren): boolean =>
  node.title.trim() === '' &&
  node.body.trim() === '' &&
  (node.children?.length ?? 0) === 0;

export const pruneEmptyContentTreeChildren = (
  nodes: ContentTreeChildren[],
): ContentTreeChildren[] =>
  nodes
    .map((node) => ({
      ...node,
      ...(node.children && {
        children: pruneEmptyContentTreeChildren(node.children),
      }),
    }))
    .filter((node) => !isEmptyContentTreeNode(node));

export const moveItem = <T>(
  items: T[],
  index: number,
  direction: 'up' | 'down',
): T[] => {
  const targetIndex =
    direction === 'down'
      ? (index + 1) % items.length
      : (index - 1 + items.length) % items.length;

  const result = [...items];
  [result[index], result[targetIndex]] = [result[targetIndex], result[index]];

  return result;
};

export const isEmptyContentTree = (tree: ContentTree): boolean =>
  tree.title.trim() === '' &&
  tree.intro.trim() === '' &&
  tree.outro.trim() === '' &&
  (tree.children?.length ?? 0) === 0;

// Class name (for js-logic only) that identifies a node by its number.
export const contentTreeNodeClass = (number: string): string =>
  `js-content-tree-node-${number.replaceAll('.', '-')}`;

// Moves focus after a node has been removed, since the removed node's remove
// button (which had focus) is gone. Focuses the remove button of the node that
// took its place (or the previous one when the last node was removed), or the
// add button when no nodes are left. Call after the DOM has been updated.
export const focusAfterRemoval = (
  root: ParentNode | null,
  numberPrefix: string,
  removedIndex: number,
  remainingCount: number,
  addButton: HTMLElement | null,
): void => {
  if (remainingCount === 0) {
    addButton?.focus();
    return;
  }

  const number = `${numberPrefix}${Math.min(removedIndex, remainingCount - 1) + 1}`;
  root
    ?.querySelector<HTMLElement>(
      `.${contentTreeNodeClass(number)} .js-content-tree-node-remove`,
    )
    ?.focus();
};
