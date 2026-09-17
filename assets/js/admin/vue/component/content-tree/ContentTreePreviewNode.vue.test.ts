import { md } from '@admin-fe/component/form/markdown/markdownit';
import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import ContentTreePreviewNode from './ContentTreePreviewNode.vue';
import type { ContentTreeChildren } from './interface';

describe('The "ContentTreePreviewNode" component', () => {
  const createComponent = (node: ContentTreeChildren, number = '1') =>
    mount(ContentTreePreviewNode, {
      props: { node, number },
    });

  const getHeading = (component: ReturnType<typeof createComponent>) =>
    component.find('h2, h3, h4, h5, h6');

  test('should render the title inside a heading', () => {
    const component = createComponent({ title: 'Node title', body: '' });

    expect(getHeading(component).text()).toBe('Node title');
  });

  test('should render the body as rendered markdown', () => {
    const component = createComponent({
      title: '',
      body: '**Bold text**',
    });

    expect(component.find('.bhr-content').element.innerHTML).toBe(
      md.render('**Bold text**'),
    );
  });

  test('should render the top-level node as an <h2>', () => {
    const component = createComponent({ title: '', body: '' }, '1');

    expect(getHeading(component).element.tagName).toBe('H2');
  });

  test('should render a heading level matching the depth of the node', () => {
    expect(
      createComponent({ title: '', body: '' }, '1.2').find('h3').exists(),
    ).toBe(true);
    expect(
      createComponent({ title: '', body: '' }, '1.2.3').find('h4').exists(),
    ).toBe(true);
  });

  test('should cap the heading level at <h6> for deeply nested nodes', () => {
    const component = createComponent({ title: '', body: '' }, '1.1.1.1.1.1');

    expect(getHeading(component).element.tagName).toBe('H6');
  });

  test('should render nothing for a node without children', () => {
    const component = createComponent({ title: '', body: '' });

    expect(component.findAllComponents(ContentTreePreviewNode)).toHaveLength(0);
  });

  test('should render every child node recursively', () => {
    const component = createComponent({
      title: 'Parent',
      body: '',
      children: [
        {
          title: 'Child',
          body: '',
          children: [{ title: 'Grandchild', body: '' }],
        },
        { title: 'Second child', body: '' },
      ],
    });

    expect(
      component.findAll('h2, h3, h4, h5, h6').map((heading) => heading.text()),
    ).toEqual(['Parent', 'Child', 'Grandchild', 'Second child']);
  });

  test('should derive the heading level of nested nodes from their position within the tree', () => {
    const component = createComponent(
      {
        title: 'Parent',
        body: '',
        children: [
          {
            title: 'Child',
            body: '',
            children: [{ title: 'Grandchild', body: '' }],
          },
        ],
      },
      '2',
    );

    expect(
      component
        .findAll('h2, h3, h4, h5, h6')
        .map((heading) => heading.element.tagName),
    ).toEqual(['H2', 'H3', 'H4']);
  });
});
