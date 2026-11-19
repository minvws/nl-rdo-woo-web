import { describe, expect, it } from 'vitest';
import { createIcon } from './icon';

const XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';

const render = (markup: string) => {
  document.body.innerHTML = markup;

  return document.querySelector('svg') as SVGSVGElement;
};

describe('The function which creates an icon', () => {
  it('should create an svg element', () => {
    const icon = render(createIcon({ name: 'sort', path: '/icons.svg' }));

    expect(icon).toBeInTheDocument();
  });

  it('should hide the icon from assistive technology', () => {
    const icon = render(createIcon({ name: 'sort', path: '/icons.svg' }));

    expect(icon).toHaveAttribute('aria-hidden', 'true');
  });

  it('should reference the named symbol in the given file', () => {
    const icon = render(createIcon({ name: 'sort', path: '/icons.svg' }));

    expect(
      icon.querySelector('use')?.getAttributeNS(XLINK_NAMESPACE, 'href'),
    ).toBe(`/icons.svg?${__GIT_HASH__}#sort`);
  });

  it('should use a size of 24 for width and height by default', () => {
    const icon = render(createIcon({ name: 'sort', path: '/icons.svg' }));

    expect(icon).toHaveAttribute('width', '24');
    expect(icon).toHaveAttribute('height', '24');
  });

  it('should use the size for width and height', () => {
    const icon = render(
      createIcon({ name: 'sort', path: '/icons.svg', size: 16 }),
    );

    expect(icon).toHaveAttribute('width', '16');
    expect(icon).toHaveAttribute('height', '16');
  });

  it('should use the width and height when no size is given', () => {
    const icon = render(
      createIcon({
        height: 10,
        name: 'sort',
        path: '/icons.svg',
        width: 20,
      }),
    );

    expect(icon).toHaveAttribute('width', '20');
    expect(icon).toHaveAttribute('height', '10');
  });

  it('should let the size override an explicit width and height', () => {
    const icon = render(
      createIcon({
        height: 10,
        name: 'sort',
        path: '/icons.svg',
        size: 16,
        width: 20,
      }),
    );

    expect(icon).toHaveAttribute('width', '16');
    expect(icon).toHaveAttribute('height', '16');
  });

  it('should fall back to 24 for a dimension that is not given', () => {
    const icon = render(
      createIcon({ name: 'sort', path: '/icons.svg', width: 20 }),
    );

    expect(icon).toHaveAttribute('width', '20');
    expect(icon).toHaveAttribute('height', '24');
  });

  it('should add the color and class name to the svg', () => {
    const icon = render(
      createIcon({
        className: 'extra',
        color: 'fill-black',
        name: 'sort',
        path: '/icons.svg',
      }),
    );

    expect(icon).toHaveAttribute('class', 'fill-black extra');
  });
});
