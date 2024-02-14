import { getCurrentLocale } from './locale';

export const formatNumber = (number: number): string => new Intl.NumberFormat(getCurrentLocale(), { maximumFractionDigits: 2 })
  .format(number);
