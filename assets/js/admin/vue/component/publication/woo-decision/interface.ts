import { z } from 'zod';

export enum UploadStatus {
  Confirmed = 'confirmed',
  Completed = 'completed',
  NeedsConfirmation = 'needs_confirmation',
  OpenForUploads = 'open_for_uploads',
  ProcessingUpdates = 'processing_updates',
  ProcessingUploads = 'processing_uploads',
  Rejected = 'rejected',
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
