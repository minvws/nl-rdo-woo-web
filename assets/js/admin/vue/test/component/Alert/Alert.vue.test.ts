import Alert from '@admin-fe/component/Alert.vue';
import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "Alert" component', () => {
  interface Options {
    type?: 'danger' | 'info' | 'success';
  }

  const createComponent = (options: Options = {}) => {
    const { type } = options;

    return mount(Alert, {
      props: {
        type,
      },
      slots: {
        default: 'This is the provided alert content',
      },
    });
  };

  const getIconComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'Icon' });
  const getIconName = (component: VueWrapper) =>
    getIconComponent(component).props().name;

  test('should display the provided content', () => {
    expect(createComponent().text()).toContain(
      'This is the provided alert content',
    );
  });

  describe('a danger alert', () => {
    const component = createComponent({ type: 'danger' });

    test('should be styled with danger colors', () => {
      expect(component.classes()).toContain('bhr-alert--danger');
    });

    test('should display a danger icon', () => {
      expect(getIconName(component)).toBe('exclamation-colored');
    });
  });

  describe('an info alert', () => {
    const component = createComponent({ type: 'info' });

    test('should be styled with info colors', () => {
      expect(component.classes()).toContain('bhr-alert--info');
    });

    test('should display an info icon', () => {
      expect(getIconName(component)).toBe('info-rounded-filled');
    });
  });

  describe('a success alert', () => {
    const component = createComponent();

    test('should be styled with success colors', () => {
      expect(component.classes()).toContain('bhr-alert--success');
    });

    test('should display a checkmark icon', () => {
      expect(getIconName(component)).toBe('check-rounded-filled');
    });
  });
});
