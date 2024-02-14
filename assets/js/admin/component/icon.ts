import filePath from '@img/admin/icons.svg';

import { icon as iconComponent, IconProperties } from '@js/component';

export const icon = (properties: IconProperties) => {
  const { color = 'fill-bhr-dim-gray' } = properties;
  return iconComponent({ ...properties, color, filePath });
};
