import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import FormLabel from './FormLabel.vue';

describe('The "<FormLabel />" component', () => {
  const createComponent = (required?: boolean) =>
    mount(FormLabel, {
      props: {
        for: 'mocked-for-value',
        required,
      },
      slots: {
        default: 'Mocked provided content',
      },
    });

  const getLabelElement = (component: VueWrapper) => component.find('label');

  test('should render a label element with the provided for value and content', () => {
    const component = createComponent();
    const labelElement = getLabelElement(component);

    expect(labelElement.text()).toBe('Mocked provided content');
    expect(labelElement.attributes('for')).toBe('mocked-for-value');
    expect(labelElement.classes()).toContain('bhr-label');
  });

  test('should render the text "(optioneel)" when the "required" prop is false', () => {
    expect(getLabelElement(createComponent(false)).text()).toBe(
      'Mocked provided content (optioneel)',
    );
  });
});
