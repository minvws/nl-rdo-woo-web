export const formatList = (
  list: string[],
  finalGlue: string,
  regularGlue = ', ',
): string => {
  if (Array.isArray(list) === false) {
    return '';
  }

  const { length } = list;

  if (length === 0) {
    return '';
  }

  if (length === 1) {
    return list[0];
  }

  if (length === 2) {
    return list.join(` ${finalGlue} `);
  }

  const lastItem: string | undefined = list.pop();
  return `${list.join(regularGlue)} ${finalGlue} ${lastItem}`;
};
