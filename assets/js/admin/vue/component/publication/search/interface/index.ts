import { z } from 'zod';

export const publicationSearchResultSchema = z.object({
  id: z.string(),
  link: z.string(),
  number: z.string().nullable(),
  title: z.string(),
  type: z.enum(['attachment', 'document', 'dossier', 'main_document']),
});

export const publicationSearchResultsSchema = z.array(
  publicationSearchResultSchema,
);

export type PublicationSearchResult = z.infer<
  typeof publicationSearchResultSchema
>;
