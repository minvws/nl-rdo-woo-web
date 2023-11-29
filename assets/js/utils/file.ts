export const formatFileSize = (bytes: number): string => {
  const units = ['Bytes', 'KB', 'MB', 'GB'];
  let size = bytes;
  let unitIndex = 0;

  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024;
    unitIndex += 1;
  }

  const toFixed = size.toFixed(2);
  const formattedSize = toFixed.endsWith('.00') ? size.toFixed(0) : toFixed;

  return `${formattedSize} ${units[unitIndex]}`;
};

export const getIconNameByMimeType = (mimeType: string) => {
  const mappings = {
    'file-audio': ['audio/mp4', 'audio/mpeg'],
    'file-csv': ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    'file-pdf': ['application/pdf'],
    'file-video': ['video/mp4'],
    'file-word': ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    'file-zip': ['application/zip'],
  };

  const foundIconName = (Object.keys(mappings) as (keyof typeof mappings)[]).find((key) => mappings[key].includes(mimeType));

  return foundIconName || 'file-unknown';
};

export const areFilesEqual = (file1: File, file2: File) => {
  const properties: (keyof File)[] = ['lastModified', 'name', 'size', 'type'];
  return properties.every((property) => file1[property] === file2[property]);
};
