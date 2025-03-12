export interface AttachmentType {
  label: string;
  type: 'AttachmentType';
  value: string;
}

export interface AttachmentTypeBranch {
  attachmentTypes: AttachmentType[];
  label: string;
  subbranch: AttachmentTypeBranch | null;
  type: 'AttachmentTypeBranch';
}

export type PublicationFileType = AttachmentType | AttachmentTypeBranch;
export type PublicationFileTypes = Record<number, PublicationFileType>;
