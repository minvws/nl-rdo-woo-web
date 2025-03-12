import { AttachmentType } from '@admin-fe/component/publication/file/interface';

export const createMockedAttachmentType = (
  value: string,
  label: string,
): AttachmentType => ({
  type: 'AttachmentType',
  value,
  label,
});

export const createMockedAttachmentTypes = (
  prefix: string,
  amount: number,
): AttachmentType[] =>
  Array.from({ length: amount }, (_, index) =>
    createMockedAttachmentType(
      `${prefix}.value_${index + 1}`,
      `${prefix}.label_${index + 1}`,
    ),
  );
