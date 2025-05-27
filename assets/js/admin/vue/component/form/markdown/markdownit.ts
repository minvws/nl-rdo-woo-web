import markdownit from 'markdown-it';

export const md = markdownit('zero').enable([
  'emphasis',
  'heading',
  'link',
  'list',
  'paragraph',
]);

md.renderer.rules.heading_open = (tokens, idx) => {
  if (tokens[idx].tag === 'h1') {
    return '<h2>';
  }
  return `<${tokens[idx].tag}>`;
};

md.renderer.rules.heading_close = (tokens, idx) => {
  if (tokens[idx].tag === 'h1') {
    return '</h2>';
  }
  return `</${tokens[idx].tag}>`;
};

md.renderer.rules.em_open = () => '<span class="bhr-italic">';
md.renderer.rules.em_close = () => '</span>';

md.renderer.rules.strong_open = () => '<span class="bhr-bold">';
md.renderer.rules.strong_close = () => '</span>';
