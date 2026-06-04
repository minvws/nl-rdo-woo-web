import { z } from 'zod';

export const publicationFileSchema = z.object({
  dossier: z.object({
    id: z.string(),
  }),
  formalDate: z.string().date(),
  grounds: z.array(z.string()),
  id: z.string().optional(),
  internalReference: z.string(),
  language: z.enum([
    'NLD',
    'BUL',
    'DAN',
    'DEU',
    'ENG',
    'EST',
    'FIN',
    'FRA',
    'FRY',
    'ELL',
    'HUN',
    'GLE',
    'ITA',
    'HRV',
    'LAV',
    'LIT',
    'MLT',
    'PAP-AW',
    'PAP-CW',
    'POL',
    'POR',
    'RON',
    'SLV',
    'SLK',
    'SPA',
    'CES',
    'SWE',
  ]),
  mimeType: z.string(),
  name: z.string(),
  size: z.number(),
  type: z.string(),
  withdrawUrl: z.string().optional(),
});

export const publicationFilesSchema = z.array(publicationFileSchema);
export type PublicationFile = z.infer<typeof publicationFileSchema>;
