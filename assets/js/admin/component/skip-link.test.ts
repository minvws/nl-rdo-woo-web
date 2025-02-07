import { describe, expect, test } from 'vitest';
import { skipLink } from './skip-link';

describe('The "skipLink" function', () => {
  const element = skipLink({
    css: 'mocked-provided-class-name',
    content: 'mocked_content',
    href: '#mocked-link',
    id: 'mocked-skip-link',
  });

  test('should return an anchor element', () => {
    expect(element.tagName).toBe('A');
  });

  test('should return an anchor element with some skip link class names and the provided class name', () => {
    const expectedClassNames = [
      'sr-only',
      'focus:not-sr-only',
      'focus:bhr-a',
      'focus:no-underline',
      'focus:inline-block',
      'focus:p-2',
      'mocked-provided-class-name',
    ];

    expect(
      [...element.classList].every((className) =>
        expectedClassNames.includes(className),
      ),
    ).toBe(true);
  });

  test('should return an anchor element with the provided id', () => {
    expect(element.id).toBe('mocked-skip-link');
  });

  test('should return an anchor element with the provided text', () => {
    expect(element.textContent).toBe('mocked_content');
  });

  test('should return an anchor element with the provided link', () => {
    expect(element.getAttribute('href')).toBe('#mocked-link');
  });
});
