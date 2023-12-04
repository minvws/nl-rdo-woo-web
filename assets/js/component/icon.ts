export interface IconProperties {
  css?: string;
  color?: string;
  filePath?: string;
  name: string;
  size?: number;
}

export const icon = (properties: IconProperties) => {
  const {
    css = '',
    color = 'fill-dim-gray',
    filePath = '',
    name,
    size = 24,
  } = properties;
  return `<svg aria-hidden="true" class="inline-block ${color} ${css}" height="${size}" width="${size}">
    <use xlink:href="${filePath}#${name}" />
  </svg>`;
};
