import { formatNumber } from '@utils';

export const MimeTypes: Readonly<Record<string, string[]>> = {
  Audio: ['audio/aac', 'audio/mp3', 'audio/ogg', 'audio/vnd.wav'],
  Csv: ['application/vnd.ms-excel', 'application/msexcel'],
  Pdf: [
    'application/pdf',
    'application/acrobat',
    'application/nappdf',
    'application/x-pdf',
    'image/pdf',
  ],
  Presentation: [
    'application/mspowerpoint',
    'application/powerpoint',
    'application/vnd.ms-powerpoint',
    'application/x-mspowerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
    'application/vnd.ms-powerpoint',
    'application/mspowerpoint',
    'application/powerpoint',
    'application/x-mspowerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/vnd.oasis.opendocument.presentation',
  ],
  Spreadsheet: [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.oasis.opendocument.spreadsheet',
    'application/vnd.oasis.opendocument.formula',
  ],
  Text: ['text/plain', 'text/rtf'],
  Video: ['video/mp4'],
  Word: [
    'application/msword',
    'application/vnd.ms-word',
    'application/x-msword',
    'zz-application/zz-winassoc-doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.oasis.opendocument.text',
  ],
  Xml: ['application/rdf+xml'],
  Zip: ['application/zip', 'application/x-7z-compressed'],
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
    'file-audio': MimeTypes.Audio,
    'file-csv': [...MimeTypes.Csv, ...MimeTypes.Spreadsheet],
    'file-pdf': MimeTypes.Pdf,
    'file-presentation': MimeTypes.Presentation,
    'file-text': MimeTypes.Text,
    'file-video': MimeTypes.Video,
    'file-word': MimeTypes.Word,
    'file-xml': MimeTypes.Xml,
    'file-zip': MimeTypes.Zip,
  };

  const foundIconName = (
    Object.keys(mappings) as (keyof typeof mappings)[]
  ).find((key) => mappings[key].includes(mimeType));

  return foundIconName || 'file-unknown';
};

export const getFileTypeByMimeType = (mimeType: string) => {
  const mappings = {
    audio: MimeTypes.Audio,
    csv: MimeTypes.Csv,
    spreadsheet: MimeTypes.Spreadsheet,
    pdf: MimeTypes.Pdf,
    presentatie: MimeTypes.Presentation,
    text: MimeTypes.Text,
    video: MimeTypes.Video,
    Word: MimeTypes.Word,
    xml: MimeTypes.Xml,
    zip: MimeTypes.Zip,
  };

  return (
    (Object.keys(mappings) as (keyof typeof mappings)[]).find((key) =>
      mappings[key].includes(mimeType),
    ) ?? 'onbekend'
  );
};

export const areFilesEqual = (file1: File, file2: File) => {
  const properties: (keyof File)[] = ['lastModified', 'name', 'size', 'type'];
  return properties.every((property) => file1[property] === file2[property]);
};
