import { DocumentMock } from '../mocks';

const cryptoMock = {
  randomUUID: jest.fn().mockReturnValue('some-mocked-random-uuid'),
};

const documentMock: DocumentMock = {
  addEventListener: jest.fn(),
  readyState: 'loading',
};

const locationMock = {
  assign: jest.fn(),
};

const windowMock = {
  addEventListener: jest.fn(),
  crypto: cryptoMock,
  location: locationMock,
  matchMedia: jest.fn().mockReturnValue({ matches: false }),
  print: jest.fn(),
};

export const getCrypto = () => cryptoMock;
export const getDocument = () => documentMock;
export const getLocation = () => locationMock;
export const getWindow = () => windowMock;
