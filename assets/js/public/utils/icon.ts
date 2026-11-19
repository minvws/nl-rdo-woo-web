import { createIcon } from '@utils';

const ICON_PATH = '/img/public/icons.svg';

interface PublicIconOptions {
  className?: string;
  color?: string;
  name: string;
  size?: number;
}

export const createPublicIcon = ({
  className = '',
  color = 'fill-current',
  name,
  size,
}: PublicIconOptions) =>
  createIcon({
    className: `woo-icon ${className}`.trim(),
    color,
    name,
    path: ICON_PATH,
    size,
  });
