import { z } from 'zod';

export enum UploadStatus {
  OpenForUploads = 'open_for_uploads',
  ProcessingUploads = 'processing_uploads',
  NeedsConfirmation = 'needs_confirmation',
  Confirmed = 'confirmed',
  MaxSizeExceeded = 'max_size_exceeded',
  Rejected = 'rejected',
  ProcessingUpdates = 'processing_updates',
  Completed = 'completed',
  NoChanges = 'no_changes',
}

export const uploadedWooDecisionDocumentSchema = z.object({
  id: z.string().uuid(),
  name: z.string(),
  mimeType: z.string(),
});

export type UploadedWooDecisionDocumentFile = z.infer<
  typeof uploadedWooDecisionDocumentSchema
>;

export const wooDecisionUploadStatusResponseSchema = z.object({
  canProcess: z.boolean(),
  changes: z.object({
    add: z.number().optional(),
    republish: z.number().optional(),
    update: z.number().optional(),
  }),
  currentDocumentsCount: z.number(),
  dossierId: z.string().uuid(),
  expectedDocumentsCount: z.number(),
  missingDocuments: z.array(z.string()),
  status: z.nativeEnum(UploadStatus),
  uploadedFiles: z.array(uploadedWooDecisionDocumentSchema),
});

export type WooDecisionUploadStatusResponse = z.infer<
  typeof wooDecisionUploadStatusResponseSchema
>;
