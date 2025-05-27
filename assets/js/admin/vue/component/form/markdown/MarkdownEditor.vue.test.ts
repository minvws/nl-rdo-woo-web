import { DOMWrapper, VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import { nextTick } from 'vue';
import MarkdownEditor from './MarkdownEditor.vue';
import { md } from './markdownit';

describe('The "MarkdownEditor" component', () => {
  const mockedMarkdown = `**This is bold text**

        _This is italic text_

        [This is a link](https://www.example.com)

        * List item 1 of unordered list
        * List item 2 of unordered list

        1. List item 1 of ordered list
        2. List item 2 of ordered list\r`;

  const createComponent = (value = mockedMarkdown) => {
    return mount(MarkdownEditor, {
      props: {
        id: 'mocked-id',
        name: 'mocked-name',
        value,
      },
    });
  };

  const getTextareaElement = (wrapper: VueWrapper) => wrapper.find('textarea');
  const getTextareaValue = (wrapper: VueWrapper) =>
    getTextareaElement(wrapper).element.value;
  const getPreviewHtml = (wrapper: VueWrapper) =>
    wrapper.find('.preview').element.innerHTML;

  const getToolbarButtonAt = (wrapper: VueWrapper, index: number) =>
    wrapper.findAll('button').at(index) as DOMWrapper<HTMLButtonElement>;
  const toggleBold = (wrapper: VueWrapper) => toggleToolbarButtonAt(wrapper, 0);
  const toggleItalic = (wrapper: VueWrapper) =>
    toggleToolbarButtonAt(wrapper, 1);
  const toggleLink = (wrapper: VueWrapper) => toggleToolbarButtonAt(wrapper, 2);
  const toggleBulletList = (wrapper: VueWrapper) =>
    toggleToolbarButtonAt(wrapper, 3);
  const toggleNumberedList = (wrapper: VueWrapper) =>
    toggleToolbarButtonAt(wrapper, 4);
  const toggleToolbarButtonAt = async (wrapper: VueWrapper, index: number) => {
    getToolbarButtonAt(wrapper, index).trigger('click');
    await nextTick();
  };

  const selectRange = (wrapper: VueWrapper, start: number, end?: number) => {
    getTextareaElement(wrapper).element.setSelectionRange(start, end ?? start);
  };

  const selectText = (wrapper: VueWrapper, text: string) => {
    const start = getTextareaValue(wrapper).indexOf(text);
    const end = start + text.length;
    selectRange(wrapper, start, end);
  };

  const getSelectedText = (wrapper: VueWrapper) => {
    const textarea = getTextareaElement(wrapper).element;
    return textarea.value.substring(
      textarea.selectionStart,
      textarea.selectionEnd,
    );
  };

  const updateValue = (wrapper: VueWrapper, value: string) =>
    getTextareaElement(wrapper).setValue(value);

  describe('the textarea element', () => {
    test('should have the correct id and name', () => {
      const textarea = getTextareaElement(createComponent());
      expect(textarea.attributes()).toMatchObject({
        id: 'mocked-id',
        name: 'mocked-name',
      });
    });

    test('should have the provided value with carriage returns replaced by an empty string', () => {
      const textarea = getTextareaElement(createComponent());
      expect(textarea.element.value).toBe(mockedMarkdown.replace(/\r/g, ''));
    });
  });

  test('should have a preview element containing the html rendered from the markdown', async () => {
    const component = createComponent();
    expect(getPreviewHtml(component)).toBe(md.render(mockedMarkdown));

    const updatedMarkdown = '**This is updated bold text**';
    await updateValue(component, updatedMarkdown);
    expect(getPreviewHtml(component)).toBe(md.render(updatedMarkdown));
  });

  describe('toggling the bold style', () => {
    test('should make unbold text bold and bold text unbold', async () => {
      const component = createComponent('This is **bold** text');

      await toggleBold(component);
      expect(getTextareaValue(component)).toBe('This is **bold** **text**');

      await toggleBold(component);
      expect(getTextareaValue(component)).toBe('This is **bold** text');

      selectText(component, 'bold');
      await toggleBold(component);
      expect(getTextareaValue(component)).toBe('This is bold text');
      expect(getSelectedText(component)).toBe('bold');

      selectText(component, 'bold');
      await toggleBold(component);
      expect(getTextareaValue(component)).toBe('This is **bold** text');
      expect(getSelectedText(component)).toBe('bold');

      selectText(component, '**bold**');
      await toggleBold(component);
      expect(getTextareaValue(component)).toBe('This is bold text');
      expect(getSelectedText(component)).toBe('bold');
    });
  });

  describe('toggling the italic style', () => {
    test('should make unitalic text italic and italic text unitalic', async () => {
      const component = createComponent('This is _italic_ text');

      await toggleItalic(component);
      expect(getTextareaValue(component)).toBe('This is _italic_ _text_');

      await toggleItalic(component);
      expect(getTextareaValue(component)).toBe('This is _italic_ text');

      selectText(component, 'italic');
      await toggleItalic(component);
      expect(getTextareaValue(component)).toBe('This is italic text');
      expect(getSelectedText(component)).toBe('italic');

      selectText(component, 'italic');
      await toggleItalic(component);
      expect(getTextareaValue(component)).toBe('This is _italic_ text');
      expect(getSelectedText(component)).toBe('italic');

      selectText(component, '_italic_');
      await toggleItalic(component);
      expect(getTextareaValue(component)).toBe('This is italic text');
      expect(getSelectedText(component)).toBe('italic');
    });
  });

  describe('toggling a numbered list', () => {
    test('should remove or add a numbered list', async () => {
      const markdown =
        '1. List item 1 of a numbered list\n2. List item 2 of a numbered list';
      const component = createComponent(markdown);

      selectRange(component, 0, markdown.length);
      await toggleNumberedList(component);
      expect(getTextareaValue(component)).toBe(
        'List item 1 of a numbered list\nList item 2 of a numbered list',
      );

      selectRange(component, 0, markdown.length);
      await toggleNumberedList(component);
      expect(getTextareaValue(component)).toBe(markdown);

      selectRange(component, getTextareaValue(component).length);
      await toggleNumberedList(component);
      expect(getTextareaValue(component)).toBe(
        '1. List item 1 of a numbered list\nList item 2 of a numbered list',
      );

      selectText(component, 'List item 2 of a numbered list');
      await toggleNumberedList(component);
      expect(getTextareaValue(component)).toBe(
        '1. List item 1 of a numbered list\n\n1. List item 2 of a numbered list',
      );
    });
  });

  describe('toggling a bullet list', () => {
    test('should remove or add a bullet list', async () => {
      const markdown =
        '- List item 1 of a bullet list\n- List item 2 of a bullet list';
      const component = createComponent(markdown);

      selectRange(component, 0, markdown.length);
      await toggleBulletList(component);
      expect(getTextareaValue(component)).toBe(
        'List item 1 of a bullet list\nList item 2 of a bullet list',
      );

      selectRange(component, 0, markdown.length);
      await toggleBulletList(component);
      expect(getTextareaValue(component)).toBe(markdown);

      selectRange(component, getTextareaValue(component).length);
      await toggleBulletList(component);
      expect(getTextareaValue(component)).toBe(
        '- List item 1 of a bullet list\nList item 2 of a bullet list',
      );

      selectText(component, 'List item 2 of a bullet list');
      await toggleBulletList(component);
      expect(getTextareaValue(component)).toBe(
        '- List item 1 of a bullet list\n\n- List item 2 of a bullet list',
      );
    });
  });

  test('should support adding a link', async () => {
    const component = createComponent('This is a link');

    await toggleLink(component);
    expect(getTextareaValue(component)).toBe('This is a [link](url)');

    updateValue(component, 'This is a link');

    selectText(component, 'a link');
    await toggleLink(component);
    expect(getTextareaValue(component)).toBe('This is [a link](url)');

    updateValue(component, 'This is a link');

    selectText(component, 's a lin');
    await toggleLink(component);
    expect(getTextareaValue(component)).toBe('This i[s a lin](url)k');
    expect(getSelectedText(component)).toBe('url');
  });
});
