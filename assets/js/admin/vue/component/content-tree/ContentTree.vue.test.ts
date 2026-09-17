import AddButton from '@admin-fe/component/button/AddButton.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import ContentTree from './ContentTree.vue';
import ContentTreeNode from './ContentTreeNode.vue';
import type { ContentTreeChildren } from './interface';

describe('The "ContentTree" component', () => {
  const emptyContentTree = {
    title: '',
    intro: '',
    children: [],
    outro: '',
  };

  const mockedValue = JSON.stringify({
    title: '',
    intro: '',
    outro: '',
    children: [
      {
        title: 'Node',
        body: 'Body',
        children: [
          { title: 'Child', body: 'Child body', children: [] },
          { title: 'Second child', body: 'Second child body' },
        ],
      },
    ],
  });

  const createComponent = (value = mockedValue) =>
    mount(ContentTree, {
      props: {
        id: 'mocked-id',
        name: 'mocked-name',
        value,
      },
    });

  const getFormTextarea = (component: ReturnType<typeof createComponent>) =>
    component.find<HTMLTextAreaElement>('#mocked-id');

  const getTextareaValue = (value?: string) =>
    getFormTextarea(createComponent(value)).element.value;

  const getNodes = (component: ReturnType<typeof createComponent>) =>
    component.findAllComponents(ContentTreeNode);

  // The root editor also renders a title input and intro/outro markdown
  // textareas, so title inputs/body textareas are scoped to nodes (which
  // live inside a "[data-level]" wrapper) to keep index-based lookups
  // pointed at the same elements as before.
  const getTitleInputs = (component: ReturnType<typeof createComponent>) =>
    component
      .findAll<HTMLInputElement>('input[type="text"]')
      .filter((input) => input.element.closest('[data-level]') !== null);

  const getBodyTextareas = (component: ReturnType<typeof createComponent>) =>
    component
      .findAll<HTMLTextAreaElement>('textarea:not(#mocked-id)')
      .filter((textarea) => textarea.element.closest('[data-level]') !== null);

  const getRootTitleInput = (component: ReturnType<typeof createComponent>) =>
    component
      .findAll<HTMLInputElement>('input[type="text"]')
      .filter((input) => input.element.closest('[data-level]') === null)[0];

  const getRootIntroOutroTextareas = (
    component: ReturnType<typeof createComponent>,
  ) =>
    component
      .findAll<HTMLTextAreaElement>('textarea:not(#mocked-id)')
      .filter((textarea) => textarea.element.closest('[data-level]') === null);

  // MarkdownEditor renders its own "Preview" heading inside each node, so
  // headings are filtered down to the node's own "Onderwerp ..." heading.
  const findHeadings = (component: ReturnType<typeof createComponent>) =>
    component
      .findAll('h2, h3, h4, h5, h6')
      .filter((heading) => heading.text().startsWith('Onderwerp'));

  const getHeadings = (component: ReturnType<typeof createComponent>) =>
    findHeadings(component).map((heading) => heading.text());

  const getHeadingTags = (component: ReturnType<typeof createComponent>) =>
    findHeadings(component).map((heading) => heading.element.tagName);

  const getOwnButtons = (
    wrapper: ReturnType<typeof createComponent>,
    element: Element | null = null,
  ) =>
    wrapper
      .findAll('button')
      .filter((button) => button.element.closest('[data-level]') === element);

  const getOwnAddButton = (
    wrapper: ReturnType<typeof createComponent>,
    element: Element | null = null,
  ) =>
    wrapper
      .findAllComponents(AddButton)
      .filter(
        (button) => button.element.closest('[data-level]') === element,
      )[0];

  const getNodeAddButton = (node: ReturnType<typeof getNodes>[number]) =>
    getOwnAddButton(node, node.element);

  const getNodeRemoveButton = (node: ReturnType<typeof getNodes>[number]) =>
    getOwnButtons(node, node.element).filter(
      (button) => button.element !== getNodeAddButton(node).element,
    )[0];

  const getTreeAddButton = (component: ReturnType<typeof createComponent>) =>
    getOwnAddButton(component);

  test('should render a hidden and readonly textarea using the provided id and name', () => {
    const textarea = getFormTextarea(createComponent());

    expect(textarea.attributes('id')).toBe('mocked-id');
    expect(textarea.attributes('name')).toBe('mocked-name');
    expect(textarea.attributes('hidden')).toBeDefined();
    expect(textarea.attributes('readonly')).toBeDefined();
  });

  test('should dispatch an input event on the textarea, carrying its up-to-date value, whenever the json changes', async () => {
    const component = createComponent();
    const textarea = getFormTextarea(component).element;
    const valuesSeenByListener: string[] = [];
    textarea.addEventListener('input', () => {
      valuesSeenByListener.push(textarea.value);
    });

    await getTitleInputs(component)[1].setValue('Updated child');

    expect(valuesSeenByListener).toHaveLength(1);
    expect(
      JSON.parse(valuesSeenByListener[0]).children[0].children[0].title,
    ).toBe('Updated child');
  });

  test('should display the provided value as json', () => {
    expect(JSON.parse(getTextareaValue())).toEqual(JSON.parse(mockedValue));
  });

  test('should display a default content tree object when no value is provided', () => {
    expect(JSON.parse(getTextareaValue(''))).toEqual(emptyContentTree);
  });

  test('should display a default content tree object when the value is invalid json', () => {
    expect(JSON.parse(getTextareaValue('{ not json'))).toEqual(
      emptyContentTree,
    );
  });

  test('should display a default content tree object when the value is a legacy (list-shaped) content tree', () => {
    expect(
      JSON.parse(
        getTextareaValue('[{"title":"Node","body":"Body","children":[]}]'),
      ),
    ).toEqual(emptyContentTree);
  });

  test('should merge a partial content tree object with defaults', () => {
    expect(JSON.parse(getTextareaValue('{"title":"Node"}'))).toEqual({
      title: 'Node',
      intro: '',
      children: [],
      outro: '',
    });
  });

  test('should render an input and a textarea for every node, at any depth', () => {
    const component = createComponent();

    expect(
      getTitleInputs(component).map((input) => input.element.value),
    ).toEqual(['Node', 'Child', 'Second child']);
    expect(
      getBodyTextareas(component).map((textarea) => textarea.element.value),
    ).toEqual(['Body', 'Child body', 'Second child body']);
  });

  test('should render the title input and intro/outro textareas for the tree itself', () => {
    const component = createComponent(
      JSON.stringify({
        title: 'Tree title',
        intro: 'Tree intro',
        outro: 'Tree outro',
        children: [],
      }),
    );

    expect(getRootTitleInput(component).element.value).toBe('Tree title');
    expect(
      getRootIntroOutroTextareas(component).map((t) => t.element.value),
    ).toEqual(['Tree intro', 'Tree outro']);
  });

  test('should update the json when the title, intro or outro of the tree changes', async () => {
    const component = createComponent('');

    await getRootTitleInput(component).setValue('New title');
    await getRootIntroOutroTextareas(component)[0].setValue('New intro');
    await getRootIntroOutroTextareas(component)[1].setValue('New outro');

    expect(JSON.parse(getFormTextarea(component).element.value)).toEqual({
      title: 'New title',
      intro: 'New intro',
      children: [],
      outro: 'New outro',
    });
  });

  test('should update the json when the title of a nested node changes', async () => {
    const component = createComponent();

    await getTitleInputs(component)[1].setValue('Updated child');

    expect(JSON.parse(getFormTextarea(component).element.value)).toEqual({
      title: '',
      intro: '',
      outro: '',
      children: [
        {
          title: 'Node',
          body: 'Body',
          children: [
            { title: 'Updated child', body: 'Child body', children: [] },
            { title: 'Second child', body: 'Second child body' },
          ],
        },
      ],
    });
  });

  test('should update the json when the body of a node changes', async () => {
    const component = createComponent();

    await getBodyTextareas(component)[0].setValue('Updated body');

    expect(
      JSON.parse(getFormTextarea(component).element.value).children[0].body,
    ).toBe('Updated body');
  });

  test('should label the add and remove buttons of every node', () => {
    const node = getNodes(createComponent())[0];

    expect(getNodeAddButton(node).text()).toBe('Onderwerp 1.3 toevoegen');
    expect(getNodeRemoveButton(node).text()).toBe('Onderwerp 1 verwijderen');
  });

  test('should number every node after its position within the tree', () => {
    const component = createComponent(
      JSON.stringify({
        title: '',
        intro: '',
        outro: '',
        children: [
          {
            title: 'First',
            body: '',
            children: [
              { title: 'First child', body: '' },
              {
                title: 'Second child',
                body: '',
                children: [{ title: 'Grandchild', body: '' }],
              },
              { title: 'Third child', body: '' },
            ],
          },
          { title: 'Second', body: '' },
        ],
      }),
    );

    expect(getHeadings(component)).toEqual([
      'Onderwerp 1',
      'Onderwerp 1.1',
      'Onderwerp 1.2',
      'Onderwerp 1.2.1',
      'Onderwerp 1.3',
      'Onderwerp 2',
    ]);
  });

  test('should renumber the nodes when one of them is removed', async () => {
    const component = createComponent();

    expect(getHeadings(component)).toEqual([
      'Onderwerp 1',
      'Onderwerp 1.1',
      'Onderwerp 1.2',
    ]);

    await getNodeRemoveButton(getNodes(component)[1]).trigger('click');

    expect(getHeadings(component)).toEqual(['Onderwerp 1', 'Onderwerp 1.1']);
  });

  test('should add an empty node when the add button of the tree is used', async () => {
    const component = createComponent('');

    expect(getTreeAddButton(component).text()).toBe('Onderwerp 1 toevoegen');

    await getTreeAddButton(component).trigger('click');

    expect(getHeadings(component)).toEqual(['Onderwerp 1']);
  });

  test('should add an empty child node when the add button of a node is used', async () => {
    const component = createComponent();

    await getNodeAddButton(getNodes(component)[0]).trigger('click');

    expect(getHeadings(component)).toEqual([
      'Onderwerp 1',
      'Onderwerp 1.1',
      'Onderwerp 1.2',
      'Onderwerp 1.3',
    ]);
  });

  test('should add an empty child node to a node without children', async () => {
    const component = createComponent();

    await getNodeAddButton(getNodes(component)[2]).trigger('click');

    expect(getHeadings(component)).toEqual([
      'Onderwerp 1',
      'Onderwerp 1.1',
      'Onderwerp 1.2',
      'Onderwerp 1.2.1',
    ]);
  });

  test('should remove a nested node when its remove button is used', async () => {
    const component = createComponent();

    await getNodeRemoveButton(getNodes(component)[1]).trigger('click');

    expect(
      JSON.parse(getFormTextarea(component).element.value).children[0].children,
    ).toEqual([{ title: 'Second child', body: 'Second child body' }]);
  });

  test('should remove a root node when its remove button is used', async () => {
    const component = createComponent();

    await getNodeRemoveButton(getNodes(component)[0]).trigger('click');

    expect(JSON.parse(getFormTextarea(component).element.value)).toEqual(
      emptyContentTree,
    );
  });

  test('should use a heading level matching the depth of a node', () => {
    const createNestedNodes = (depth: number): ContentTreeChildren[] =>
      depth === 0
        ? []
        : [{ title: '', body: '', children: createNestedNodes(depth - 1) }];

    // Seven levels deep, so the deepest two nodes both have to settle for <h6>.
    const component = createComponent(
      JSON.stringify({
        title: '',
        intro: '',
        outro: '',
        children: createNestedNodes(7),
      }),
    );

    expect(getHeadingTags(component)).toEqual([
      'H2',
      'H3',
      'H4',
      'H5',
      'H6',
      'H6',
      'H6',
    ]);
  });

  test('should drop an empty root node from the posted json', async () => {
    const component = createComponent('');

    await getTreeAddButton(component).trigger('click');

    expect(JSON.parse(getFormTextarea(component).element.value)).toEqual(
      emptyContentTree,
    );
  });

  test('should drop an empty child node from the posted json, keeping its non-empty parent', async () => {
    const component = createComponent();

    await getNodeAddButton(getNodes(component)[0]).trigger('click');

    expect(
      JSON.parse(getFormTextarea(component).element.value).children[0].children,
    ).toEqual([
      { title: 'Child', body: 'Child body', children: [] },
      { title: 'Second child', body: 'Second child body' },
    ]);
  });

  test('should keep a node whose title and body are empty when it has a non-empty child', async () => {
    const component = createComponent(
      JSON.stringify({
        title: '',
        intro: '',
        outro: '',
        children: [
          { title: '', body: '', children: [{ title: 'Child', body: '' }] },
        ],
      }),
    );

    expect(
      JSON.parse(getFormTextarea(component).element.value).children,
    ).toEqual([
      { title: '', body: '', children: [{ title: 'Child', body: '' }] },
    ]);
  });

  test('should name the number the next node will get in the add buttons', async () => {
    const component = createComponent();
    const getAddButtonTexts = () => [
      getTreeAddButton(component).text(),
      ...getNodes(component).map((node) => getNodeAddButton(node).text()),
    ];

    expect(getAddButtonTexts()).toEqual([
      'Onderwerp 2 toevoegen',
      'Onderwerp 1.3 toevoegen',
      'Onderwerp 1.1.1 toevoegen',
      'Onderwerp 1.2.1 toevoegen',
    ]);

    await getNodeAddButton(getNodes(component)[0]).trigger('click');

    expect(getAddButtonTexts()).toEqual([
      'Onderwerp 2 toevoegen',
      'Onderwerp 1.4 toevoegen',
      'Onderwerp 1.1.1 toevoegen',
      'Onderwerp 1.2.1 toevoegen',
      'Onderwerp 1.3.1 toevoegen',
    ]);
  });
});
