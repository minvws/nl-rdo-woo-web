const isOne = (numberOf: number) => numberOf === 1;

export const capitalize = (string: string) => string.charAt(0).toUpperCase() + string.slice(1);
export const pluralize = (singular: string, plural: string, numberOf: number) => (isOne(numberOf) ? singular : plural);
