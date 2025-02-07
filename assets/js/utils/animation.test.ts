import { afterEach, describe, expect, test, vi } from 'vitest';
import { isAnimationDisabled } from './animation';

const mockedMatchMediaFunction = vi.fn();

vi.mock('./browser', () => ({
  getWindow: () => ({
    matchMedia: mockedMatchMediaFunction,
  }),
}));

describe('The animation utility functions', () => {
  describe('the "isAnimationDisabled" function', () => {
    afterEach(() => {
      mockedMatchMediaFunction.mockReturnValue({ matches: false });
    });

    test('should return "true" if the user has reduced animations in his/her settings', () => {
      mockedMatchMediaFunction.mockReturnValue({ matches: true });
      expect(isAnimationDisabled()).toBe(true);
    });

    test('should return "false" if the user has not reduced animations in his/her settings', () => {
      expect(isAnimationDisabled()).toBe(false);
    });
  });
});
