import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import FormButton from './FormButton.vue';

describe('The "<FormButton />" component', () => {
  const createComponent = (isSecondary = false) =>
    mount(FormButton, {
      props: {
        isSecondary,
      },
      slots: {
        default: 'Mocked provided content',
      },
    });

  const getButtonElement = (component: VueWrapper) => component.find('button');

  test('should render a primary submit button by default', () => {
    const component = createComponent();
    const buttonElement = getButtonElement(component);
    const buttonClasses = buttonElement.classes();

    expect(buttonElement.text()).toBe('Mocked provided content');
    expect(buttonElement.attributes('type')).toBe('submit');
    expect(buttonClasses).toContain('bhr-btn-filled-primary');
    expect(buttonClasses).not.toContain('bhr-btn-bordered-primary');
  });

  test('should render a secondary button when the "isSecondary" prop is true', () => {
    const component = createComponent(true);
    const buttonElement = getButtonElement(component);
    const buttonClasses = buttonElement.classes();

    expect(buttonElement.attributes('type')).toBe('button');
    expect(buttonClasses).toContain('bhr-btn-bordered-primary');
    expect(buttonClasses).not.toContain('bhr-btn-filled-primary');
  });
});
