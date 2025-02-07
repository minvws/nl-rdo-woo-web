import { isValidMaxFileSize } from './file';

export const validateFiles = (
  files: File[],
  mimeTypes: string[],
  maxFileSize?: number,
) => {
  const invalidSizeFiles = new Set<File>();
  const invalidTypeFiles = new Set<File>();
  const validFiles = new Set<File>();

  const mimeTypesSet = new Set(mimeTypes);
  const hasValidMimeTypesDefined = mimeTypesSet.size > 0;
  const hasValidMaxFileSize = isValidMaxFileSize(maxFileSize);

  const hasValidMimeType = (file: File) => {
    if (!hasValidMimeTypesDefined) {
      return true;
    }

    if (file.type === '') {
      // Firefox seems to have a bug where the file type is empty for files in a dragged directory
      return true;
    }

    return mimeTypesSet.has(file.type);
  };

  const hasValidSize = (file: File) => {
    if (!hasValidMaxFileSize) {
      return true;
    }

    return file.size <= (maxFileSize as number);
  };

  files.forEach((file) => {
    const hasMimeTypeError = !hasValidMimeType(file);
    const hasSizeError = !hasValidSize(file);

    if (hasMimeTypeError) {
      invalidTypeFiles.add(file);
    }

    if (hasSizeError) {
      invalidSizeFiles.add(file);
    }

    if (!hasMimeTypeError && !hasSizeError) {
      validFiles.add(file);
    }
  });

  return {
    invalidSize: [...invalidSizeFiles.values()],
    invalidType: [...invalidTypeFiles.values()],
    valid: [...validFiles.values()],
  };
};
