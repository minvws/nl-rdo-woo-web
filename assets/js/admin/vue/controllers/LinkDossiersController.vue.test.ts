import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import LinkDossiersController from './LinkDossiersController.vue';

describe('The "LinkDossiersController" component', () => {
  const mockedSubmitErrors = ['mocked_submit-error'];

  const createComponent = () =>
    mount(LinkDossiersController, {
      props: {
        endpoint: 'mocked_endpoint',
        name: 'mocked_name',
        submitErrors: mockedSubmitErrors,
      },
      shallow: true,
    });

  const getLinkDossiersComponent = () =>
    createComponent().findComponent({ name: 'LinkDossiers' });

  test('should render a <LinkDossiers /> component', async () => {
    const linkDossiersComponent = getLinkDossiersComponent();

    expect(linkDossiersComponent.props('endpoint')).toEqual('mocked_endpoint');
    expect(linkDossiersComponent.props('name')).toEqual('mocked_name');
    expect(linkDossiersComponent.props('submitErrors')).toEqual(
      mockedSubmitErrors,
    );
  });
});
