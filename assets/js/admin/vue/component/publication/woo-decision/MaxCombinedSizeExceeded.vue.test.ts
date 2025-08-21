import { mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import MaxCombinedSizeExceeded from './MaxCombinedSizeExceeded.vue';

describe('The "<MaxCombinedSizeExceeded />" component', () => {
  const createComponent = () =>
    mount(MaxCombinedSizeExceeded, {
      props: {
        rejectEndpoint: 'mocked-reject-endpoint',
        maxSize: 1024 * 1024 * 1024 * 8,
      },
      shallow: true,
      global: {
        renderStubDefaultSlot: true,
      },
    });

  beforeEach(() => {
    window.fetch = vi.fn().mockImplementation(() =>
      Promise.resolve({
        json: () => Promise.resolve(),
      }),
    );
  });

  afterEach(() => {
    vi.resetAllMocks();
  });

  const getAlertComponent = (wrapper: VueWrapper) =>
    wrapper.findComponent({ name: 'Alert' });

  const getButtonElement = (wrapper: VueWrapper) => wrapper.find('button');

  test('should display a message saying the maximum combined size of all files is exceeded', async () => {
    const alertComponent = getAlertComponent(createComponent());
    expect(alertComponent.props('type')).toBe('danger');
    expect(alertComponent.text()).toContain(
      'De maximaal toegestane grootte van alle bestanden samen is overschreden. Upload de bestanden opnieuw en zorg ervoor dat ze samen niet groter zijn dan 8 GB.',
    );
  });

  describe('the button', () => {
    test('should make a request to the provided reject endpoint when clicked', async () => {
      const component = createComponent();
      const buttonElement = getButtonElement(component);

      expect(buttonElement.attributes('type')).toBe('button');
      expect(buttonElement.text()).toBe('Bestanden opnieuw uploaden');
      expect(fetch).not.toHaveBeenCalled();

      await buttonElement.trigger('click');
      expect(fetch).toHaveBeenCalledWith('mocked-reject-endpoint', {
        method: 'POST',
      });
    });

    test('should emit a "rejected" event when clicked', async () => {
      const component = createComponent();
      const buttonElement = getButtonElement(component);

      expect(component.emitted('rejected')).toBeUndefined();

      await buttonElement.trigger('click');
      expect(component.emitted('rejected')).toBeDefined();
    });
  });
});
