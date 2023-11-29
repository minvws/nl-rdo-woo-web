import { getCrypto } from './browser';

export const uniqueId = (prefix = '', length = 8, glue = '-') => {
  const id = `${getCrypto().randomUUID()}-${getCrypto().randomUUID()}`;
  const formattedPrefix = prefix !== '' ? `${prefix}${glue}` : '';
  return `${formattedPrefix}${id.substring(0, length)}`;
};
