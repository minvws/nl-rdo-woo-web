import { PublicationFile } from '@admin-fe/component/publication/file/interface';

interface Options extends Omit<PublicationFile, 'dossier'> {
  dossierId: string;
}
export const createMockedPublicationFile = (
  options: Partial<Options> = {},
): PublicationFile => ({
  dossier: {
    id: options.dossierId ?? 'mocked-dossier-id',
  },
  formalDate: options.formalDate ?? 'mocked-formal-date',
  grounds: options.grounds ?? ['mocked-ground-1', 'mocked-ground-2'],
  internalReference: options.internalReference ?? 'mocked-internal-reference',
  language: options.language ?? 'Dutch',
  mimeType: options.mimeType ?? 'mocked-mime-type',
  name: options.name ?? 'mocked-name',
  size: options.size ?? 100,
  type: options.type ?? 'mocked-type',
});
