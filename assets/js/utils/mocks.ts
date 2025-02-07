import { Mock } from 'vitest';

export interface DocumentMock {
  addEventListener: Mock;
  documentElement: {
    getAttribute: Mock;
  };
  readyState: Document['readyState'];
}
