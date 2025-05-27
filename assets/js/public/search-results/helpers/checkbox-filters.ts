import { appendToParams, deleteFromParams, getSearchParams } from './params';

interface NameAndValue {
  name: string;
  value: string;
}

export const getCheckboxFilterElements = () =>
  queryCheckboxFilterElements(document);

export const queryCheckboxFilterElements = (
  element: Element | Document | null,
) => {
  if (!element) {
    return [];
  }

  return [
    ...element.querySelectorAll<HTMLInputElement>('.js-search-filter-checkbox'),
  ];
};

export const getUpdatedParamsFromCheckboxFilter = (
  checkboxElement: HTMLInputElement | undefined,
  shouldAppend = true,
) => {
  if (!checkboxElement) {
    return getSearchParams();
  }

  const updatedParams = getNamesAndValues(checkboxElement).reduce(
    (params, { name, value }) => {
      if (shouldAppend) {
        return appendToParams(params, name, value);
      }
      return deleteFromParams(params, name, value);
    },
    getSearchParams(),
  );

  return possiblyeRemoveTopLevelKey(updatedParams, checkboxElement);
};

const possiblyeRemoveTopLevelKey = (
  params: URLSearchParams,
  checkboxElement: HTMLInputElement,
) => {
  const groupedCheckBoxes = getGroupedCheckboxes(checkboxElement);
  if (groupedCheckBoxes.length === 0) {
    return params;
  }

  const numberOfCheckedCheckboxes = groupedCheckBoxes.reduce(
    (count, element) =>
      params.has(element.name, element.value) ? count + 1 : count,
    0,
  );

  if (numberOfCheckedCheckboxes === 1) {
    const { name, value } = getNameAndValue(groupedCheckBoxes[0]);
    return deleteFromParams(params, name, value);
  }

  return params;
};

const getGroupedCheckboxes = (checkboxElement: HTMLInputElement) =>
  queryCheckboxFilterElements(
    checkboxElement.closest('.js-search-filter-checkbox-group'),
  );

export const getNamesAndValues = (
  checkboxElement: HTMLInputElement,
): NameAndValue[] => {
  if (!isTopLevelCheckbox(checkboxElement)) {
    return [getNameAndValue(checkboxElement)];
  }

  return getGroupedCheckboxes(checkboxElement).map(getNameAndValue);
};

const getNameAndValue = (checkboxElement: HTMLInputElement): NameAndValue => {
  const { name, value } = checkboxElement;
  return { name, value };
};

const isTopLevelCheckbox = (checkboxElement: HTMLInputElement) =>
  checkboxElement === getTopLevelCheckbox(checkboxElement);
const getTopLevelCheckbox = (checkboxElement: HTMLInputElement) =>
  getGroupedCheckboxes(checkboxElement)[0];
