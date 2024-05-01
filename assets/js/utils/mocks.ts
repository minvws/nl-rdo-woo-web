import { Mock } from 'vitest';

export interface DocumentMock {
  addEventListener: Mock;
  readyState: Document['readyState'];
}
