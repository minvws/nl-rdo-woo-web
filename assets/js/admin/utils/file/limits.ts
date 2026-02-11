import type { FileUploadLimit } from './interface';

export const collectFileLimitLabels = (limits: FileUploadLimit[]) => {
  const labels = [...new Set(limits.map((limit) => limit.label))];
  return labels.sort((a, b) => a.localeCompare(b));
};

export const collectFileLimitSizes = (limits: FileUploadLimit[]) => {
  const sizes = [
    ...new Set(
      limits
        .filter((limit) => limit.size !== undefined)
        .map((limit) => limit.size as number),
    ),
  ];
  return sizes.sort((a, b) => a - b);
};

export const collectFileLimitMimeTypes = (limits: FileUploadLimit[]) => {
  const mimeTypes = [
    ...new Set(limits.flatMap((limit) => limit.mimeTypes ?? [])),
  ];
  return mimeTypes.sort((a, b) => a.localeCompare(b));
};

export const hasFileUploadLimits = (limits: FileUploadLimit[]) =>
  limits.length > 0;
