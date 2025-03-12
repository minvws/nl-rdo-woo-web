import { VueWrapper, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import { PublicationSearchResult } from './search/interface';
import LinkDossiers from './LinkDossiers.vue';

describe('The "LinkDossiers" component', () => {
  interface Options {
    submitErrors: string[];
  }

  const createComponent = (options: Partial<Options> = {}) => {
    const { submitErrors = [] } = options;

    return mount(LinkDossiers, {
      props: {
        name: 'mocked-name',
        submitErrors,
      },
      shallow: true,
      global: {
        stubs: {
          PublicationSearchAutocomplete: false,
          Dialog: false,
        },
      },
    });
  };

  const getTitleContent = (component: VueWrapper) =>
    component.find('h2').text();

  const getOutputElement = (component: VueWrapper) => component.find('output');

  const getErrorMessagesComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'ErrorMessages' });

  const getPublicationSearchAutocompleteComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'PublicationSearchAutocomplete' });

  const getSelectedDossiersList = (component: VueWrapper) =>
    getOutputElement(component).find('ul');

  const getButtonByText = (component: VueWrapper, text: string) =>
    component.findAll('button').find((button) => {
      return button.text().includes(text);
    });

  const getAddDossierButton = (component: VueWrapper) =>
    getButtonByText(component, 'Koppelen');

  const getSelectElementOptions = (component: VueWrapper) =>
    component.findAll('select[name="mocked-name"] option');

  const getDialogComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'Dialog' });

  const getCancelButton = (component: VueWrapper) =>
    getButtonByText(component, 'Annuleren');

  const getChooseDecisionButton = (component: VueWrapper) =>
    getButtonByText(component, '+ Kies besluit...');

  const selectDossier = async (
    component: VueWrapper,
    dossier: PublicationSearchResult,
  ) => {
    const publicationSearchAutocompleteComponent =
      getPublicationSearchAutocompleteComponent(component);
    return await publicationSearchAutocompleteComponent.vm.$emit(
      'select',
      dossier,
    );
  };

  const addDossier = async (
    component: VueWrapper,
    dossier: PublicationSearchResult,
  ) => {
    await selectDossier(component, dossier);
    return await getAddDossierButton(component)?.trigger('click');
  };

  beforeEach(() => {
    HTMLDialogElement.prototype.close = vi.fn(); // This prevents a warning saying this function does not exist
    HTMLDialogElement.prototype.showModal = vi.fn(); // This prevents a warning saying this function does not exist
  });

  test('should display a title having the text "Gepubliceerde besluiten"', () => {
    const component = createComponent();
    expect(getTitleContent(component)).toBe('Gepubliceerde besluiten');
  });

  test('should display the provided submit errors', () => {
    const component = createComponent({ submitErrors: ['error-message'] });
    expect(getErrorMessagesComponent(component).props('messages')).toEqual([
      'error-message',
    ]);
  });

  test('should mark the component as invalid when submit errors are provided', async () => {
    const component = createComponent();
    const getInvalidElement = () => component.find('.bhr-form-row--invalid');

    expect(getInvalidElement().exists()).toBe(false);

    await component.setProps({ submitErrors: ['error-message'] });
    expect(getInvalidElement().exists()).toBe(true);
  });

  describe('when no dossiers are selected', () => {
    test('should display the text "Nog niets gekozen"', () => {
      const component = createComponent();
      expect(getOutputElement(component).text()).toBe('Nog niets gekozen');
    });
  });

  test('should display the selected dossiers', async () => {
    const component = createComponent();

    expect(getSelectedDossiersList(component).exists()).toBe(false);

    await addDossier(component, { id: '1', title: 'Dossier 1' });
    await addDossier(component, { id: '2', title: 'Dossier 2' });

    const selectedDossiersList = getSelectedDossiersList(component);
    expect(selectedDossiersList.findAll('li').length).toBe(2);
    expect(selectedDossiersList.text()).toContain('Dossier 1');
    expect(selectedDossiersList.text()).toContain('Dossier 2');
  });

  test('should add the selected dossiers to a hidden select input element', async () => {
    const component = createComponent();

    expect(getSelectElementOptions(component).length).toBe(0);

    await addDossier(component, { id: 'id-1', title: 'Dossier 1' });
    await addDossier(component, { id: 'id-2', title: 'Dossier 2' });

    const options = getSelectElementOptions(component);

    expect(options.length).toBe(2);
    expect(options.at(1)?.element.getAttribute('value')).toBe('id-2');
  });

  test('should have a closed dialog by default', () => {
    const component = createComponent();
    expect(getDialogComponent(component).props('modelValue')).toBe(false);
  });

  test('should open the dialog when the user clicks the "Kies een besluit" button', async () => {
    const component = createComponent();
    await getChooseDecisionButton(component)?.trigger('click');
    const dialogComponent = getDialogComponent(component);
    expect(dialogComponent.props('modelValue')).toBe(true);
    expect(dialogComponent.props('title')).toBe('Kies een besluit');
  });

  test('should close the dialog when pressing the cancel button', async () => {
    const component = createComponent();
    await getChooseDecisionButton(component)?.trigger('click');
    const dialogComponent = getDialogComponent(component);
    expect(dialogComponent.props('modelValue')).toBe(true);

    await getCancelButton(component)?.trigger('click');
    expect(dialogComponent.props('modelValue')).toBe(false);
  });
});
