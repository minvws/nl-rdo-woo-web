import type { Optgroup, SelectOption } from '@admin-fe/form';
import { capitalize } from '@js/utils';
import type {
  AttachmentType,
  AttachmentTypeBranch,
  PublicationFileType,
  PublicationFileTypes,
} from '../interface';

export const collectAllSelectOptionsFromFileTypes = (
  publicationFileTypes: PublicationFileTypes,
): SelectOption[] => {
  const options = getSelectOptionsFromFileTypes(publicationFileTypes);
  const optgroupOptions = getOptgroupsFromFileTypes(
    publicationFileTypes,
  ).reduce(
    (acc, optgroup) => [...acc, ...optgroup.options],
    [] as SelectOption[],
  );
  return [...options, ...optgroupOptions];
};

export const findFileTypeLabelByValue = (
  publicationFileTypes: PublicationFileTypes,
  value: string,
): string => {
  const options = collectAllSelectOptionsFromFileTypes(publicationFileTypes);
  if (options.length === 1) {
    return capitalize(options[0].label);
  }

  const found = options.find((option) => option.value === value);
  return found ? capitalize(found.label) : '';
};

export const getOptgroupsFromFileTypes = (
  publicationFileTypes: PublicationFileTypes,
): Optgroup[] =>
  getOptgroupsFromFileTypeBranches(
    Object.values(publicationFileTypes).filter(isAttachmentTypeBranch),
    [],
  );

const getOptgroupsFromFileTypeBranches = (
  attachmentTypeBranches: AttachmentTypeBranch[],
  optGroups: Optgroup[],
): Optgroup[] =>
  attachmentTypeBranches.reduce(
    (acc, attachmentTypeBranch) => [
      ...acc,
      {
        label: attachmentTypeBranch.label,
        options: getSelectOptionsFromAttachmentTypes(
          attachmentTypeBranch.attachmentTypes,
        ),
      },
      ...getOptgroupsFromFileTypeBranch(attachmentTypeBranch.subbranch, acc),
    ],
    optGroups,
  );

const getOptgroupsFromFileTypeBranch = (
  attachmentTypeBranch: AttachmentTypeBranch | null,
  optGroups: Optgroup[],
): Optgroup[] => {
  if (!attachmentTypeBranch) {
    return [];
  }

  const optGroup = {
    label: attachmentTypeBranch.label,
    options: getSelectOptionsFromAttachmentTypes(
      attachmentTypeBranch.attachmentTypes,
    ),
  };
  return [
    optGroup,
    ...getOptgroupsFromFileTypeBranch(
      attachmentTypeBranch.subbranch,
      optGroups,
    ),
  ];
};

const getSelectOptionsFromAttachmentTypes = (
  attachmentTypes: AttachmentType[],
): SelectOption[] =>
  attachmentTypes.map((attachmentType) => ({
    label: attachmentType.label,
    value: attachmentType.value,
  }));

export const getValuesFromPublicationFileTypes = (
  publicationFileTypes: PublicationFileTypes,
): string[] =>
  collectAllSelectOptionsFromFileTypes(publicationFileTypes).map(
    (option) => option.value,
  );

export const getSelectOptionsFromFileTypes = (
  publicationFileTypes: PublicationFileTypes,
): SelectOption[] =>
  Object.values(publicationFileTypes)
    .filter(isAttachmentType)
    .map((publicationFileType) => ({
      label: publicationFileType.label,
      value: publicationFileType.value,
    }));

const isAttachmentType = (
  publicationFileType: PublicationFileType,
): publicationFileType is AttachmentType =>
  publicationFileType.type === 'AttachmentType';

const isAttachmentTypeBranch = (
  publicationFileType: PublicationFileType,
): publicationFileType is AttachmentTypeBranch =>
  publicationFileType.type === 'AttachmentTypeBranch';
