import { formatNumber, isNumber } from '@utils';

const enum MimeType {
  Csv = 'application/csv',
  Pdf = 'application/pdf',
  PdfX = 'application/x-pdf',
  MsExcel = 'application/vnd.ms-excel',
  OpenDocumentSpeadsheet = 'application/vnd.oasis.opendocument.spreadsheet',
  OfficeDocumentSpreadsheet = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  SevenZip = 'application/x-7z-compressed',
  VideoMp4 = 'video/mp4',
  Word = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  Zip = 'application/zip',
}

export const MimeTypes: Readonly<Record<string, string[]>> = {
  Csv: [MimeType.Csv],
  Pdf: [MimeType.Pdf],
  Spreadsheet: [MimeType.OpenDocumentSpeadsheet, MimeType.OfficeDocumentSpreadsheet],
  Video: [MimeType.VideoMp4],
  Word: [MimeType.Word],
  Zip: [MimeType.Zip, MimeType.SevenZip],
};

export const formatFileSize = (bytes: number): string => {
  const units = ['Bytes', 'KB', 'MB', 'GB'];
  let size = bytes;
  let unitIndex = 0;

  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024;
    unitIndex += 1;
  }

  return `${formatNumber(size)} ${units[unitIndex]}`;
};

export const getIconNameByMimeType = (mimeType: string) => {
  const mappings = {
    'file-csv': [...MimeTypes.Csv, ...MimeTypes.Spreadsheet],
    'file-pdf': MimeTypes.Pdf,
    'file-video': MimeTypes.Video,
    'file-word': MimeTypes.Word,
    'file-zip': MimeTypes.Zip,
  };

  const foundIconName = (Object.keys(mappings) as (keyof typeof mappings)[]).find((key) => mappings[key].includes(mimeType));

  return foundIconName || 'file-unknown';
};

export const getFileTypeByMimeType = (mimeType: string) => {
  const mappings = {
    csv: MimeTypes.Csv,
    spreadsheet: MimeTypes.Spreadsheet,
    pdf: MimeTypes.Pdf,
    video: MimeTypes.Video,
    Word: MimeTypes.Word,
    zip: MimeTypes.Zip,
  };

  return (Object.keys(mappings) as (keyof typeof mappings)[]).find((key) => mappings[key].includes(mimeType)) ?? 'onbekend';
};

export const isValidMaxFileSize = (maxFileSize: unknown): boolean => isNumber(maxFileSize) && Number(maxFileSize) > 0;

const getExtenstionByMimeType = (mimeType: string): string | undefined => {
  const mappings: Record<string, string[]> = {
    '.mp4': [MimeType.VideoMp4],
    '.pdf': [MimeType.Pdf],
    '.ods': [MimeType.OpenDocumentSpeadsheet],
    '.xlsx': [MimeType.OfficeDocumentSpreadsheet],
    '.7z': [MimeType.SevenZip],
    '.docx': [MimeType.Word],
    '.zip': [MimeType.Zip],
  };

  return (Object.keys(mappings) as (keyof typeof mappings)[]).find((key) => mappings[key].includes(mimeType));
};

export const getExtenstionsByMimeTypes = (mimeTypes: string[]): string[] => {
  const map: Set<string> = mimeTypes.reduce((accumulated, mimeType) => {
    const extension = getExtenstionByMimeType(mimeType);
    if (extension) {
      return accumulated.add(extension);
    }
    return accumulated;
  }, new Set<string>());

  const extensions = Array.from(map.values());
  extensions.sort();
  return [...extensions];
};

export const areFilesEqual = (file1: File, file2: File) => {
  const properties: (keyof File)[] = ['lastModified', 'name', 'size', 'type'];
  return properties.every((property) => file1[property] === file2[property]);
};
