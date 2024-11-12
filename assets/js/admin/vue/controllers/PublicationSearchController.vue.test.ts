import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import PublicationSearchController from './PublicationSearchController.vue';

describe('The "PublicationSearchController" component', () => {
  const createComponent = () =>
    mount(PublicationSearchController, {
      props: {
        endpoint: 'mocked_endpoint',
        label: 'mocked_label',
      },
      shallow: true,
    });

  const getPublicationSearchAndGoComponent = () =>
    createComponent().findComponent({ name: 'PublicationSearchAndGo' });

  test('should render a <PublicationSearchAndGo /> component', async () => {
    const publicationSearchAndGoComponent =
      getPublicationSearchAndGoComponent();

    expect(publicationSearchAndGoComponent.props('endpoint')).toEqual(
      'mocked_endpoint',
    );
    expect(publicationSearchAndGoComponent.props('label')).toEqual(
      'mocked_label',
    );
  });
});
