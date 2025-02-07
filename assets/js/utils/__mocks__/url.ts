import { getWindow } from './browser';

export const getCurrentOrigin = () => getWindow().location.origin;
