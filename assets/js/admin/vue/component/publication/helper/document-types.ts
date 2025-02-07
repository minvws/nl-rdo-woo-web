import type { Optgroup, SelectOption } from '@admin-fe/form';
import { capitalize } from '@js/utils';

interface AttachmentType {
  label: string;
  type: 'AttachmentType';
  value: string;
}

interface AttachmentTypeBranch {
  attachmentTypes: AttachmentType[];
  label: string;
  subbranch: AttachmentTypeBranch | null;
  type: 'AttachmentTypeBranch';
}

type DocumentType = AttachmentType | AttachmentTypeBranch;
export type DocumentTypes = Record<number, DocumentType>;

export const collectAllSelectOptionsFromDocumentTypes = (
  documentTypes: DocumentTypes,
): SelectOption[] => {
  const options = getSelectOptionsFromDocumentTypes(documentTypes);
  const optgroupOptions = getOptgroupsFromDocumentTypes(documentTypes).reduce(
    (acc, optgroup) => [...acc, ...optgroup.options],
    [] as SelectOption[],
  );
  return [...options, ...optgroupOptions];
};

export const findDocumentTypeLabelByValue = (
  documentTypes: DocumentTypes,
  value: string,
): string => {
  const options = collectAllSelectOptionsFromDocumentTypes(documentTypes);
  if (options.length === 1) {
    return capitalize(options[0].label);
  }

  const found = options.find((option) => option.value === value);
  return found ? capitalize(found.label) : '';
};

export const getOptgroupsFromDocumentTypes = (
  documentTypes: DocumentTypes,
): Optgroup[] =>
  getOptgroupsFromDocumentTypeBranches(
    Object.values(documentTypes).filter(isAttachmentTypeBranch),
  );

const getOptgroupsFromDocumentTypeBranches = (
  attachmentTypeBranches: AttachmentTypeBranch[] = [],
  optGroups: Optgroup[] = [],
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
      ...getOptgroupsFromDocumentTypeBranch(
        attachmentTypeBranch.subbranch,
        acc,
      ),
    ],
    optGroups,
  );

const getOptgroupsFromDocumentTypeBranch = (
  attachmentTypeBranch: AttachmentTypeBranch | null,
  optGroups: Optgroup[] = [],
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
    ...getOptgroupsFromDocumentTypeBranch(
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

export const getSelectOptionsFromDocumentTypes = (
  documentTypes: DocumentTypes,
): SelectOption[] =>
  Object.values(documentTypes)
    .filter(isAttachmentType)
    .map((documentType) => ({
      label: documentType.label,
      value: documentType.value,
    }));

const isAttachmentType = (
  documentType: DocumentType,
): documentType is AttachmentType => documentType.type === 'AttachmentType';

const isAttachmentTypeBranch = (
  documentType: DocumentType,
): documentType is AttachmentTypeBranch =>
  documentType.type === 'AttachmentTypeBranch';
