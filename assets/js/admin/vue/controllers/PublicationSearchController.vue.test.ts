import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import PublicationSearchController from './PublicationSearchController.vue';

describe('The "PublicationSearchController" component', () => {
  const createComponent = () =>
    mount(PublicationSearchController, {
      props: {
        dossierId: 'mocked_dossier_id',
        label: 'mocked_label',
      },
      shallow: true,
    });

  const getPublicationSearchAndGoComponent = () =>
    createComponent().findComponent({ name: 'PublicationSearchAndGo' });

  test('should render a <PublicationSearchAndGo /> component', async () => {
    expect(getPublicationSearchAndGoComponent().props()).toEqual({
      dossierId: 'mocked_dossier_id',
      label: 'mocked_label',
    });
  });
});
