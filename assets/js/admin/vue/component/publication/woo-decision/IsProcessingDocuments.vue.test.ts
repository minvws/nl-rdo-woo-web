import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import IsProcessingDocuments from './IsProcessingDocuments.vue';

describe('The "<IsProcessingDocuments />" component', () => {
  const createComponent = () => mount(IsProcessingDocuments);

  test('should display a message saying documents are being processed', async () => {
    expect(createComponent().text()).toContain(
      'We zijn nu bezig met het verwerken van de documenten.',
    );
  });

  test('should expose a function which enables to set the focus on the wrapper element', async () => {
    expect(() => {
      createComponent().vm.setFocus();
    }).not.toThrow();
  });
});
