import Icon from '@admin-fe/component/Icon.vue';
import { VueWrapper, mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "Icon" component', () => {
  interface Options {
    height: number;
    size: number;
    width: number;
  }
  const createComponent = (options: Partial<Options> = {}) =>
    mount(Icon, {
      props: {
        color: 'mocked-color',
        name: 'mocked-name',
        height: options.height,
        size: options.size,
        width: options.width,
      },
    });

  const getSvg = (component: VueWrapper) => component.find('svg');
  const getSvgHeight = (component: VueWrapper) =>
    Number(getSvg(component).attributes('height'));
  const getSvgWidth = (component: VueWrapper) =>
    Number(getSvg(component).attributes('width'));

  test('should render an svg element with the provided color and name', () => {
    const svgElement = getSvg(createComponent());
    expect(svgElement.classes()).toContain('mocked-color');
    expect(svgElement.find('use').attributes('href')).toBe(
      '/assets/img/admin/icons.svg#mocked-name',
    );
  });

  describe('the height of the svg', () => {
    test('should equal the provided height', () => {
      expect(getSvgHeight(createComponent({ height: 148 }))).toBe(148);
    });

    test('should equal the provided size if no height is given', () => {
      expect(getSvgHeight(createComponent({ size: 136 }))).toBe(136);
    });

    test('should be 24 by default', () => {
      expect(getSvgHeight(createComponent())).toBe(24);
    });
  });

  describe('regarding the width of the svg', () => {
    test('should equal the provided width', () => {
      expect(getSvgWidth(createComponent({ width: 148 }))).toBe(148);
    });

    test('should equal the provided size if no width is given', () => {
      expect(getSvgWidth(createComponent({ size: 136 }))).toBe(136);
    });

    test('should be 24 by default', () => {
      expect(getSvgWidth(createComponent())).toBe(24);
    });
  });
});
