import type { FileUploadLimit } from './interface';
import { collectFileLimitMimeTypes } from './limits';

export const validateFiles = (files: File[], limits: FileUploadLimit[]) => {
  const invalidSizeFiles = new Set<File>();
  const invalidTypeFiles = new Set<File>();
  const validFiles = new Set<File>();

  const allowedMimeTypes = new Set(collectFileLimitMimeTypes(limits));

  const hasAllowedMimeTypes = allowedMimeTypes.size > 0;

  const hasValidMimeType = (file: File) => {
    if (!hasAllowedMimeTypes) {
      return true;
    }

    if (file.type === '') {
      // Firefox seems to have a bug where the file type is empty for files in a dragged directory
      return true;
    }

    return allowedMimeTypes.has(file.type);
  };

  const hasValidSize = (file: File) => {
    const limitForMimeType = limits.find((limit) =>
      limit.mimeTypes.includes(file.type),
    );

    if (limitForMimeType?.size) {
      return file.size <= limitForMimeType.size;
    }

    return true;
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
