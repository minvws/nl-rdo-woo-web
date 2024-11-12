export const capitalize = (string: string) =>
  string.charAt(0).toUpperCase() + string.slice(1);
export const pluralize = (
  singular: string,
  plural: string,
  numberOf: number,
) => (numberOf === 1 ? singular : plural);
export const removeAccents = (input: string) =>
  input.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
