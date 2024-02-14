import { getWindow } from './browser';

export const isAnimationDisabled = () => getWindow().matchMedia('(prefers-reduced-motion: reduce)').matches === true;
