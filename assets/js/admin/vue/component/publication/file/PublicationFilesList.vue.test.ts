import { createMockedAttachmentType } from '@js/test';
import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import type { PublicationFileTypes, PublicationFile } from './interface';
import PublicationFilesList from './PublicationFilesList.vue';

describe('the <PublicationFilesList /> component', () => {
  const mockedPublicationFileTypes: PublicationFileTypes = [
    createMockedAttachmentType('mocked-value', 'Mocked label'),
  ];

  const mockedFiles: Map<string, PublicationFile> = new Map([
    [
      '1',
      {
        type: 'mocked-type-1',
        dossier: { id: 'mocked-id-1' },
        formalDate: 'mocked-date-1',
        grounds: [],
        internalReference: 'mocked-internal-reference-1',
        language: 'Dutch',
        mimeType: 'mocked-mime-type-1',
        name: 'mocked-name-1',
        size: 100,
        id: 'mocked-id-1',
        withdrawUrl: 'mocked-withdraw-url-1',
      },
    ],
    [
      '2',
      {
        type: 'mocked-type-2',
        dossier: { id: 'mocked-id-2' },
        formalDate: 'mocked-date-2',
        grounds: [],
        internalReference: 'mocked-internal-reference-2',
        language: 'Dutch',
        mimeType: 'mocked-mime-type-2',
        name: 'mocked-name-2',
        size: 100,
        id: 'mocked-id-2',
        withdrawUrl: 'mocked-withdraw-url-2',
      },
    ],
  ]);

  interface Options {
    canDelete: boolean;
  }

  const createComponent = (options: Partial<Options> = {}) => {
    const { canDelete = false } = options;
    return mount(PublicationFilesList, {
      props: {
        canDelete,
        publicationFileTypes: mockedPublicationFileTypes,
        endpoint: 'mocked-endpoint',
        files: mockedFiles,
      },
    });
  };

  const getPublicationFileItemComponents = (wrapper: VueWrapper) =>
    wrapper.findAllComponents({ name: 'PublicationFileItem' });

  const getPublicationFileItemComponentAt = (
    wrapper: VueWrapper,
    index: number,
  ) => getPublicationFileItemComponents(wrapper).at(index);

  it('should display a <PublicationFileItem /> component for each file', () => {
    const publicationFileItemComponents =
      getPublicationFileItemComponents(createComponent());

    expect(publicationFileItemComponents.length).toBe(2);

    [...mockedFiles.values()].forEach((mockedFile, index) => {
      const component = publicationFileItemComponents.at(index);
      expect(component?.props()).toMatchObject({
        id: mockedFile?.id,
        date: mockedFile?.formalDate,
        endpoint: `mocked-endpoint/${mockedFile?.id}`,
        fileName: mockedFile?.name,
        fileSize: mockedFile?.size,
        fileTypes: mockedPublicationFileTypes,
        fileTypeValue: mockedFile?.type,
        mimeType: mockedFile?.mimeType,
        withdrawUrl: mockedFile?.withdrawUrl,
      });
    });
  });

  it('should emit a "deleted" event when an uploaded file is deleted', async () => {
    const component = createComponent();
    expect(component.emitted('deleted')).toBeUndefined();

    await getPublicationFileItemComponentAt(component, 0)?.vm.$emit(
      'deleted',
      'mocked-id-1',
    );
    expect(component.emitted('deleted')?.[0]).toEqual(['mocked-id-1']);
  });

  it('should emit a "edit" event when an uploaded file is edited', async () => {
    const component = createComponent();
    expect(component.emitted('edit')).toBeUndefined();

    await getPublicationFileItemComponentAt(component, 1)?.vm.$emit(
      'edit',
      'mocked-id-2',
    );
    expect(component.emitted('edit')?.[0]).toEqual(['mocked-id-2']);
  });
});
