import filePath from '@img/admin/icons.svg';

import { icon as iconComponent, IconProperties } from '@js/component';

export const icon = (properties: IconProperties) => iconComponent({ ...properties, filePath });
