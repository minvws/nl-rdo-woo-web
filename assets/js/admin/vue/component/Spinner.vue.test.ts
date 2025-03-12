import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import Spinner from './Spinner.vue';

describe('The <Spinner /> component', () => {
  const createComponent = (size?: number) =>
    mount(Spinner, {
      props: {
        size,
      },
      shallow: true,
    });

  const getIconComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'Icon' });

  test('should display a spinning loader icon with the provided size', () => {
    const iconComponent = getIconComponent(createComponent(12));
    expect(iconComponent.props()).toMatchObject({
      name: 'loader',
      size: 12,
    });
    expect(iconComponent.classes()).toEqual(['animate-spin']);
  });
});
