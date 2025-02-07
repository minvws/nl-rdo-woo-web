import { z } from 'zod';

export const publicationSearchResultSchema = z.object({
  id: z.string(),
  link: z.string(),
  number: z.string().nullable(),
  title: z.string(),
  type: z.enum(['attachment', 'document', 'dossier', 'main_document']),
});

export const publicationFileSchema = z.object({
  dossier: z.object({
    id: z.string(),
  }),
  formalDate: z.string().date(),
  grounds: z.array(z.string()),
  id: z.string().optional(),
  internalReference: z.string(),
  language: z.enum(['Dutch', 'English']),
  mimeType: z.string(),
  name: z.string(),
  size: z.number(),
  type: z.string(),
});

export const publicationFilesSchema = z.array(publicationFileSchema);

export const publicationSearchResultsSchema = z.array(
  publicationSearchResultSchema,
);

export type PublicationSearchResult = z.infer<
  typeof publicationSearchResultSchema
>;
