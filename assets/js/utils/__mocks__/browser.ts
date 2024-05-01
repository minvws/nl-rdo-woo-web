import { vi } from 'vitest';
import { DocumentMock } from '../mocks';

const cryptoMock = {
  randomUUID: vi.fn().mockReturnValue('some-mocked-random-uuid'),
};

const documentMock: DocumentMock = {
  addEventListener: vi.fn(),
  readyState: 'loading',
};

const locationMock = {
  assign: vi.fn(),
};

const windowMock = {
  addEventListener: vi.fn(),
  crypto: cryptoMock,
  location: locationMock,
  matchMedia: vi.fn().mockReturnValue({ matches: false }),
  print: vi.fn(),
};

export const getCrypto = () => cryptoMock;
export const getDocument = () => documentMock;
export const getLocation = () => locationMock;
export const getWindow = () => windowMock;
