import { icon as iconComponent, IconProperties } from '@js/component';
import filePath from '@img/admin/icons.svg';

export const icon = (properties: IconProperties) => {
  const { color = 'fill-bhr-gray-700' } = properties;
  return iconComponent({ ...properties, color, filePath });
};
