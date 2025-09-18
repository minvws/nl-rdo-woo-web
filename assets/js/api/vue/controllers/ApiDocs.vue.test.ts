import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test, vi } from 'vitest';

vi.mock('@scalar/api-reference', () => ({
  ApiReference: {
    name: 'ApiReference',
    template: '<ApiReference />',
    props: ['configuration'],
  },
}));

import ApiDocs from './ApiDocs.vue';

describe('The <ApiDocs /> component', () => {
  const createComponent = () =>
    mount(ApiDocs, {
      props: {
        openApiUrl: 'mocked-open-api-url',
      },
      shallow: true,
    });

  const getApiReferenceComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'ApiReference' });

  test('should display the <ApiReference /> component with the provided openApiUrl', () => {
    expect(getApiReferenceComponent(createComponent()).props()).toMatchObject({
      configuration: {
        url: 'mocked-open-api-url',
      },
    });
  });
});
