import type { FileUploadLimit } from './interface';

export const collectFileLimitLabels = (limits: FileUploadLimit[]) =>
  limits.map((limit) => limit.label);

export const collectFileLimitSizes = (limits: FileUploadLimit[]) => [
  ...new Set(
    limits
      .filter((limit) => limit.size !== undefined)
      .map((limit) => limit.size as number),
  ),
];

export const collectFileLimitMimeTypes = (limits: FileUploadLimit[]) => [
  ...new Set(limits.flatMap((limit) => limit.mimeTypes ?? [])),
];

export const hasFileUploadLimits = (limits: FileUploadLimit[]) =>
  limits.length > 0;
