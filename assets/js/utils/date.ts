import { getCurrentLocale } from './locale';

export const isDateInvalid = (date: string) =>
  new Date(date).toString() === 'Invalid Date';
export const isDateValid = (date: string) => !isDateInvalid(date);
export const formatDate = (
  date: string,
  style: 'long' | 'medium' | 'short' = 'medium',
) =>
  new Intl.DateTimeFormat(getCurrentLocale(), {
    dateStyle: style,
  }).format(new Date(date));
