export const createName = (name: string, index: number) => {
  const regex = /\[(\d+)\]/;
  if (!name.match(regex)) {
    return `${name}[${index}]`;
  }
  return name.replace(regex, `[${index}]`);
};

interface Item {
  id: string;
  value: string;
}
export const getOtherValues = (itemId: string, items: Item[]) =>
  items.filter((item) => item.id !== itemId).map((item) => item.value);

export const shouldAutoFocus = (
  index: number,
  items: Item[],
  minLength = 0,
) => {
  const numberOfItems = items.length;
  if (minLength === numberOfItems) {
    return false;
  }

  if (items[index].value) {
    return false;
  }

  return index === numberOfItems - 1;
};
