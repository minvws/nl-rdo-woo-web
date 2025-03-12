import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import SkipLink from './SkipLink.vue';

describe('The <SkipLink /> component', () => {
  const createComponent = (href = 'mocked-href') =>
    mount(SkipLink, {
      props: {
        href,
        id: 'mocked-id',
      },
      slots: {
        default: 'This is the mocked default slot content',
      },
    });

  const getAnchorElement = (href?: string) => createComponent(href).find('a');

  describe('the anchor element', () => {
    test('should be invisible but focusable', () => {
      expect(getAnchorElement().classes()).toEqual([
        'sr-only',
        'focus:not-sr-only',
        'focus:bhr-a',
        'focus:no-underline',
        'focus:inline-block',
        'focus:p-2',
      ]);
    });

    test('should link to the provided href, always starting with a "#"', () => {
      expect(getAnchorElement().attributes('href')).toBe('#mocked-href');
      expect(getAnchorElement('#another-mocked-href').attributes('href')).toBe(
        '#another-mocked-href',
      );
    });

    test('should have the provided id', () => {
      expect(getAnchorElement().attributes('id')).toBe('mocked-id');
    });

    test('should display the provided content', () => {
      expect(getAnchorElement().text()).toBe(
        'This is the mocked default slot content',
      );
    });
  });
});
