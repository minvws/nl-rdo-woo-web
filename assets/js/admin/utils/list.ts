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

  const listCopy = [...list];
  const lastItem: string | undefined = listCopy.pop();
  return `${listCopy.join(regularGlue)} ${finalGlue} ${lastItem}`;
};
