const DEFAULT_SIZE = 24;

interface IconOptions {
  className?: string;
  color?: string;
  height?: number;
  name: string;
  path: string;
  size?: number;
  width?: number;
}

export const createIcon = ({
  className = '',
  color = '',
  height,
  name,
  path,
  size,
  width,
}: IconOptions) => `
  <svg
    aria-hidden="true"
    class="${`${color} ${className}`.trim()}"
    height="${size || height || DEFAULT_SIZE}"
    width="${size || width || DEFAULT_SIZE}"
  >
    <use xlink:href="${path}?${__GIT_HASH__}#${name}" />
  </svg>
`;
