import { describe, expect, test } from 'vitest';
import { icon, IconProperties } from './icon';

describe('The "icon" component', () => {
  const getIconElement = (iconProperties: Partial<IconProperties> = {}): SVGSVGElement => {
    const validIconProperties: IconProperties = { name: '', ...iconProperties };
    // document.createElement('svg') does not work: https://webhint.io/docs/user-guide/hints/hint-create-element-svg/
    const spanElement = document.createElement('span');
    spanElement.innerHTML = icon(validIconProperties);
    return spanElement.querySelector('svg') as SVGSVGElement;
  };

  test('should display the icon with the provided name in an svg element', () => {
    const filePath = 'mocked-file-path.svg';
    const name = 'mocked-icon-name';

    const iconHtml = icon({ filePath, name });
    expect(iconHtml.startsWith('<svg ')).toBe(true);
    expect(iconHtml.endsWith('</svg>')).toBe(true);
    expect(iconHtml).toContain(`<use xlink:href="${filePath}#${name}" />`);
  });

  test('should have the aria-hidden="true" attribute', () => {
    const iconElement = getIconElement();
    expect(iconElement.getAttribute('aria-hidden')).toBe('true');
  });

  describe('the size', () => {
    test('should equal the provided size', () => {
      const iconElement = getIconElement({ size: 36 });
      expect(iconElement.getAttribute('height')).toBe('36');
      expect(iconElement.getAttribute('width')).toBe('36');
    });

    test('should be 24 pixels by default', () => {
      const iconElement = getIconElement();
      expect(iconElement.getAttribute('height')).toBe('24');
      expect(iconElement.getAttribute('width')).toBe('24');
    });
  });

  describe('the color', () => {
    test('should equal the provided color', () => {
      const iconElement = getIconElement({ color: 'fill-red' });
      expect(iconElement.classList.contains('fill-woo-dim-gray')).toBe(false);
      expect(iconElement.classList.contains('fill-red')).toBe(true);
    });

    test('should be gray by default', () => {
      const iconElement = getIconElement();
      expect(iconElement.classList.contains('fill-woo-dim-gray')).toBe(true);
    });
  });
});
