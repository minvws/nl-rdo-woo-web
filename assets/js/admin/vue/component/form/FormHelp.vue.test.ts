import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test, vi } from 'vitest';
import FormHelp from './FormHelp.vue';

vi.mock('@js/utils', () => ({
  uniqueId: () => 'mocked-unique-id',
}));

vi.mock('@admin-fe/form', () => ({
  getHelpId: (prodividedId: string) => prodividedId,
}));

describe('The "<FormHelp />" component', () => {
  const createComponent = (inputId?: string) =>
    mount(FormHelp, {
      props: {
        inputId,
      },
      slots: {
        default: 'Mocked provided content',
      },
    });

  const getHelpElement = (component: VueWrapper) => component.find('div');

  test('should render a help element with the provided id and content content', () => {
    const helpElement = getHelpElement(createComponent('test-input'));

    expect(helpElement.classes()).toContain('bhr-form-help');
    expect(helpElement.attributes('id')).toBe('test-input');
    expect(helpElement.text()).toBe('Mocked provided content');
  });

  test('should render a help element with a generated id and content when no inputId is provided', () => {
    expect(getHelpElement(createComponent()).attributes('id')).toBe(
      'mocked-unique-id',
    );
  });
});
