import { vi } from 'vitest';
import { DocumentMock } from '../mocks';

const cryptoMock = {
  randomUUID: vi.fn().mockReturnValue('some-mocked-random-uuid'),
};

const documentMock: DocumentMock = {
  addEventListener: vi.fn(),
  documentElement: {
    getAttribute: vi.fn().mockReturnValue('nl'),
  },
  readyState: 'loading',
};

const locationMock = {
  assign: vi.fn(),
  origin: 'https://mocked-origin.com',
  hash: '',
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
