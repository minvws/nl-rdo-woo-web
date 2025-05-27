import { mount, VueWrapper } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import DocumentChanges from './DocumentChanges.vue';

describe('The "<DocumentChanges />" component', () => {
  const createComponent = (add = 3, republish = 2, update = 1) =>
    mount(DocumentChanges, {
      props: {
        add,
        republish,
        update,
        confirmEndpoint: 'mocked-confirm-endpoint',
        rejectEndpoint: 'mocked-reject-endpoint',
      },
      shallow: true,
      global: {
        renderStubDefaultSlot: true,
      },
    });

  const getButtons = (component: VueWrapper) => component.findAll('button');

  beforeEach(() => {
    window.fetch = vi.fn().mockImplementation(() => Promise.resolve());
  });

  afterEach(() => {
    vi.resetAllMocks();
  });

  describe('when there are changes', () => {
    test('should summarize the changes', () => {
      const componentText = createComponent().text();

      expect(componentText).toContain(
        'Weet je zeker dat je de volgende acties uit wilt voeren?',
      );
      expect(componentText).toContain('3 documenten toevoegen');
      expect(componentText).toContain('2 documenten opnieuw publiceren');
      expect(componentText).toContain('1 document vervangen');
    });

    test('should display a button which enables the user to confirm the changes', async () => {
      const button = getButtons(createComponent()).at(0);

      expect(button?.text()).toContain('Ja, verwerk documenten');

      expect(window.fetch).not.toHaveBeenCalled();

      await button?.trigger('click');
      expect(window.fetch).toHaveBeenCalledWith('mocked-confirm-endpoint', {
        method: 'POST',
      });
    });

    test('should display a button which enables the user to recject the changes', async () => {
      const button = getButtons(createComponent()).at(1);

      expect(button?.text()).toContain('Annuleren');

      expect(window.fetch).not.toHaveBeenCalled();

      await button?.trigger('click');
      expect(window.fetch).toHaveBeenCalledWith('mocked-reject-endpoint', {
        method: 'POST',
      });
    });
  });

  describe('when there are no changes', () => {
    test('should display a message saying that there are no changes', () => {
      const componentText = createComponent(0, 0, 0).text();

      expect(componentText).not.toContain(
        'Weet je zeker dat je de volgende acties uit wilt voeren?',
      );
      expect(componentText).toContain('Er zijn geen acties om uit te voeren');
    });

    test('should display a button which enables the user to go back to the upload section', async () => {
      const component = createComponent(0, 0, 0);
      const button = getButtons(component).at(0);

      expect(button?.text()).toContain('Terug naar uploaden');

      expect(component.emitted()).not.toHaveProperty('goBack');
      expect(window.fetch).not.toHaveBeenCalled();

      await button?.trigger('click');
      expect(window.fetch).toHaveBeenCalledWith('mocked-confirm-endpoint', {
        method: 'POST',
      });
      expect(component.emitted()).toHaveProperty('goBack');
    });
  });

  describe('when the user confirms the changes', () => {
    test('should display a message saying that the changes are confirmed', async () => {
      const component = createComponent(1, 1, 2);

      expect(component.text()).not.toContain('De acties worden uitgevoerd.');

      await getButtons(component).at(0)?.trigger('click');
      expect(component.text()).toContain('De acties worden uitgevoerd.');
    });

    test('should display a button which enables the user to go back to the upload section', async () => {
      const component = createComponent();

      // First click to confirm the changes
      await getButtons(component).at(0)?.trigger('click');
      expect(component.emitted()).not.toHaveProperty('goBack');

      // Second click to go back to the upload section
      await getButtons(component).at(0)?.trigger('click');
      expect(component.emitted()).toHaveProperty('goBack');
    });
  });

  describe('when the user rejects the changes', () => {
    test('should display a message saying that the changes are rejected', async () => {
      const component = createComponent();

      expect(component.text()).not.toContain('De acties zijn geannuleerd.');

      await getButtons(component).at(1)?.trigger('click');
      expect(component.text()).toContain('De acties zijn geannuleerd.');
    });

    test('should display a button which enables the user to go back to the upload section', async () => {
      const component = createComponent();

      // First click to reject the changes
      await getButtons(component).at(1)?.trigger('click');
      expect(component.emitted()).not.toHaveProperty('goBack');

      // Second click to go back to the upload section
      await getButtons(component).at(0)?.trigger('click');
      expect(component.emitted()).toHaveProperty('goBack');
    });
  });
});
