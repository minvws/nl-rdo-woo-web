export interface SkipLinkProperties {
  content: string;
  css?: string;
  href: string;
  id: string;
}

export const skipLink = (properties: SkipLinkProperties) => {
  const { content, css = '', href, id } = properties;
  const element = document.createElement('a');
  element.className = `sr-only focus:not-sr-only focus:bhr-a focus:no-underline focus:inline-block focus:p-2 ${css}`;
  element.href = href;
  element.id = id;
  element.textContent = content;
  return element;
};
