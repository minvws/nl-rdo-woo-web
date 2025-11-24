import { getCrypto } from './browser';

export const uniqueId = (prefix = '', length = 8, glue = '-') => {
  const id = `${uuidv4()}-${uuidv4()}`;
  const formattedPrefix = prefix !== '' ? `${prefix}${glue}` : '';
  return `${formattedPrefix}${id.substring(0, length)}`;
};

function uuidv4() {
  const crypto = getCrypto();

  if (crypto !== undefined && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }

  const rnds = new Uint8Array(16);
  crypto.getRandomValues(rnds);

  rnds[6] = (rnds[6] & 0x0f) | 0x40;
  rnds[8] = (rnds[8] & 0x3f) | 0x80;

  const hex = Array.from(rnds)
    .map((b) => b.toString(16).padStart(2, '0'))
    .join('');

  return `${hex.substring(0, 8)}-${hex.substring(8, 12)}-${hex.substring(12, 16)}-${hex.substring(16, 20)}-${hex.substring(20, 32)}`;
}
