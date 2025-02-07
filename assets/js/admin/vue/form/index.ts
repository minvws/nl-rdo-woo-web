import type { ValidatorMessage } from './interface';
import * as validatorMessageFunctions from './validator-message';

export * from './interface';
export * from './id';
export * from './multi-input';
export * as validators from './validator';

export const validatorMessages: Record<string, ValidatorMessage> =
  validatorMessageFunctions;
