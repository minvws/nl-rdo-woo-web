import { getWindow } from './browser';

const getCurrentOrigin = () => getWindow().location.origin;

export const getUrlProperties = (url: string) => new URL(url, getCurrentOrigin());

export const isExternalUrl = (url: string) => {
  const { origin } = getUrlProperties(url);
  return origin !== getCurrentOrigin();
};

export const isValidUrl = (url: string) => {
  try {
    // eslint-disable-next-line no-new
    new URL(url);
    return true;
  } catch {
    return false;
  }
};
