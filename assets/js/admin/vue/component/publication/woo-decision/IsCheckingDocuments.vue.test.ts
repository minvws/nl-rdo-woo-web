import { mount, type VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import IsCheckingDocuments from './IsCheckingDocuments.vue';

describe('The "<IsCheckingDocuments />" component', () => {
  const createComponent = () => mount(IsCheckingDocuments);

  const getIconComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'Icon' });

  test('should display a spinner icon', async () => {
    const iconComponent = getIconComponent(createComponent());
    expect(iconComponent.props()).toMatchObject({
      color: 'fill-bhr-blue-800',
      name: 'loader',
      size: 48,
    });
    expect(iconComponent.classes()).toContain('animate-spin');
  });

  test('should display a message saying that documents are being checked', async () => {
    expect(createComponent().text()).toContain('Bezig met controleren');
  });

  test('should expose a function which enables to set the focus on the wrapper element', async () => {
    expect(() => {
      createComponent().vm.setFocus();
    }).not.toThrow();
  });
});
