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

const isEmptyContentTreeNode = (node: ContentTreeChildren): boolean =>
  node.title === '' && node.body === '' && (node.children?.length ?? 0) === 0;

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

export const isEmptyContentTree = (tree: ContentTree): boolean =>
  tree.title === '' &&
  tree.intro === '' &&
  tree.outro === '' &&
  (tree.children?.length ?? 0) === 0;
