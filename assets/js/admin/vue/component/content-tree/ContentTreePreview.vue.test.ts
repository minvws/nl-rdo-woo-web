import { md } from '@admin-fe/component/form/markdown/markdownit';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, test } from 'vitest';
import ContentTreePreview from './ContentTreePreview.vue';
import ContentTreePreviewNode from './ContentTreePreviewNode.vue';

describe('The "ContentTreePreview" component', () => {
  const treeId = 'tree-id';

  afterEach(() => {
    document.body.innerHTML = '';
  });

  const createField = (value: string) => {
    const field = document.createElement('textarea');
    field.id = treeId;
    field.value = value;
    document.body.appendChild(field);
    return field;
  };

  const createComponent = async (value = '') => {
    createField(value);
    const component = mount(ContentTreePreview, {
      props: { treeId },
      attachTo: document.body,
    });
    await flushPromises();
    return component;
  };

  const setFieldValue = async (value: string) => {
    const field = document.getElementById(treeId) as HTMLTextAreaElement;
    field.value = value;
    field.dispatchEvent(new Event('input'));
    await flushPromises();
  };

  const getEmptyMessage = (
    component: Awaited<ReturnType<typeof createComponent>>,
  ) => component.find('.bhr-text-muted');

  test('should show a placeholder message when the tree is empty', async () => {
    const component = await createComponent();

    expect(getEmptyMessage(component).exists()).toBe(true);
  });

  test('should render the initial title, intro and outro from the field on mount', async () => {
    const component = await createComponent(
      JSON.stringify({
        title: 'De titel',
        intro: 'De intro',
        outro: 'De outro',
        children: [],
      }),
    );

    expect(component.find('h2').text()).toBe('De titel');
    expect(component.text()).toContain('De intro');
    expect(component.text()).toContain('De outro');
    expect(getEmptyMessage(component).exists()).toBe(false);
  });

  test('should render the intro and outro as rendered markdown', async () => {
    const component = await createComponent(
      JSON.stringify({
        title: '',
        intro: '**Bold intro**',
        outro: '_Italic outro_',
        children: [],
      }),
    );

    const contentBlocks = component.findAll('.bhr-content');
    expect(contentBlocks[0].element.innerHTML).toBe(
      md.render('**Bold intro**'),
    );
    expect(contentBlocks[1].element.innerHTML).toBe(
      md.render('_Italic outro_'),
    );
  });

  test('should render the parsed content tree', async () => {
    const component = await createComponent(
      JSON.stringify({
        title: '',
        intro: '',
        outro: '',
        children: [
          { title: 'Node 1', body: 'Body 1' },
          { title: 'Node 2', body: 'Body 2' },
        ],
      }),
    );

    expect(component.findAllComponents(ContentTreePreviewNode)).toHaveLength(2);
    expect(component.findAll('h2').map((heading) => heading.text())).toEqual([
      'Node 1',
      'Node 2',
    ]);
  });

  test('should drop empty nodes from the content tree', async () => {
    const component = await createComponent(
      JSON.stringify({
        title: '',
        intro: '',
        outro: '',
        children: [
          { title: '', body: '', children: [] },
          { title: 'Node', body: '' },
        ],
      }),
    );

    expect(component.findAllComponents(ContentTreePreviewNode)).toHaveLength(1);
  });

  test('should treat an invalid content tree value as empty', async () => {
    const component = await createComponent('not json');

    expect(component.findAllComponents(ContentTreePreviewNode)).toHaveLength(0);
    expect(getEmptyMessage(component).exists()).toBe(true);
  });

  test('should update the title when the field changes', async () => {
    const component = await createComponent();

    await setFieldValue(
      JSON.stringify({
        title: 'Nieuwe titel',
        intro: '',
        outro: '',
        children: [],
      }),
    );

    expect(component.find('h2').text()).toBe('Nieuwe titel');
  });

  test('should update the intro when the field changes', async () => {
    const component = await createComponent();

    await setFieldValue(
      JSON.stringify({
        title: '',
        intro: 'Nieuwe intro',
        outro: '',
        children: [],
      }),
    );

    expect(component.text()).toContain('Nieuwe intro');
  });

  test('should update the outro when the field changes', async () => {
    const component = await createComponent();

    await setFieldValue(
      JSON.stringify({
        title: '',
        intro: '',
        outro: 'Nieuwe outro',
        children: [],
      }),
    );

    expect(component.text()).toContain('Nieuwe outro');
  });

  test('should update the content tree when the field changes', async () => {
    const component = await createComponent();

    await setFieldValue(
      JSON.stringify({
        title: '',
        intro: '',
        outro: '',
        children: [{ title: 'Toegevoegd', body: '' }],
      }),
    );

    expect(component.find('h2').text()).toBe('Toegevoegd');
  });

  test('should stop listening to field changes once unmounted', async () => {
    const component = await createComponent();

    component.unmount();

    await expect(
      setFieldValue(JSON.stringify({ title: 'Na het unmounten' })),
    ).resolves.not.toThrow();
  });
});
