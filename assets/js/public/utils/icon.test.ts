import { describe, expect, it } from 'vitest';
import { createPublicIcon } from './icon';

const XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';

const render = (markup: string) => {
  document.body.innerHTML = markup;

  return document.querySelector('svg') as SVGSVGElement;
};

describe('The function which creates a public icon', () => {
  it('should reference the symbol in the public icons file', () => {
    const icon = render(createPublicIcon({ name: 'sort' }));

    expect(
      icon.querySelector('use')?.getAttributeNS(XLINK_NAMESPACE, 'href'),
    ).toBe(`/img/public/icons.svg?${__GIT_HASH__}#sort`);
  });

  it('should fill the icon with the current text color by default', () => {
    const icon = render(createPublicIcon({ name: 'sort' }));

    expect(icon).toHaveClass('fill-current', 'woo-icon');
  });

  it('should use the given color and class name', () => {
    const icon = render(
      createPublicIcon({
        className: 'extra',
        color: 'fill-black',
        name: 'sort',
      }),
    );

    expect(icon).toHaveClass('fill-black', 'woo-icon', 'extra');
    expect(icon).not.toHaveClass('fill-current');
  });

  it('should use a size of 24 by default', () => {
    const icon = render(createPublicIcon({ name: 'sort' }));

    expect(icon).toHaveAttribute('width', '24');
    expect(icon).toHaveAttribute('height', '24');
  });

  it('should use the given size', () => {
    const icon = render(createPublicIcon({ name: 'sort', size: 16 }));

    expect(icon).toHaveAttribute('width', '16');
    expect(icon).toHaveAttribute('height', '16');
  });
});
