import { z } from 'zod';

export const noticeSchema = z.object({
  id: z.string(),
  dossier: z.object({ id: z.string() }),
  documentName: z.string().nullable(),
  formalDate: z.string().date(),
  grounds: z.array(z.string()).min(1),
  explanation: z.string().nullable(),
});

export type Notice = z.infer<typeof noticeSchema>;
export type { GroundOption, GroundOptions } from '../file/interface/ground';
