import { getCurrentLocale } from './locale';

export const isNumber = (value: unknown): boolean => typeof value === 'number';

export const formatNumber = (number: number): string =>
  new Intl.NumberFormat(getCurrentLocale(), {
    maximumFractionDigits: 2,
  }).format(number);
