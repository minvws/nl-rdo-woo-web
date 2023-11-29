export interface DocumentMock {
  addEventListener: jest.Mock;
  readyState: Document['readyState'];
}
