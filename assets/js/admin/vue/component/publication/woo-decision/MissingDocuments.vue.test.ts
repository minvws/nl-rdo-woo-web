import { mount, type VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import MissingDocuments from './MissingDocuments.vue';

describe('The "<MissingDocuments />" component', () => {
  const createMockedDocuments = (numberOfDocuments = 15) =>
    Array.from({ length: numberOfDocuments }, (_, i) => `${i + 1}.pdf`);

  const createComponent = (numberOfDocuments = 15) =>
    mount(MissingDocuments, {
      props: {
        documents: createMockedDocuments(numberOfDocuments),
        expectedCount: 25,
        isProcessing: false,
      },
    });

  const hasLoaderIconComponent = (component: VueWrapper) => {
    const found = component
      .findAllComponents({ name: 'Icon' })
      .filter((icon) => icon.props('name') === 'loader');
    return found.length > 0;
  };

  const getToggleButton = (component: VueWrapper) => component.find('button');

  const getDocumentListItems = (component: VueWrapper) =>
    component.findAll('li');

  test('should display the number of missing documents and the expected count', async () => {
    const component = createComponent();
    expect(component.text()).toContain(
      'Nog te uploaden: 15 van 25 documenten.',
    );

    await component.setProps({
      documents: createMockedDocuments(1),
      expectedCount: 1,
    });
    expect(component.text()).toContain('Nog te uploaden: 1 van 1 document.');
  });

  test('should display a loader when processing', async () => {
    const component = createComponent();
    expect(hasLoaderIconComponent(component)).toBe(false);

    await component.setProps({ isProcessing: true });
    expect(hasLoaderIconComponent(component)).toBe(true);
  });

  test('should display the first 12 missing documents by default', async () => {
    const listItems = getDocumentListItems(createComponent());

    expect(listItems.length).toBe(12);
    expect(listItems[0].text()).toContain('1.pdf');
    expect(listItems[1].text()).toContain('2.pdf');
  });

  describe('the toggle button to show or hide the remaining documents', () => {
    test('should toggle the visibility of the remaining documents when clicked', async () => {
      const component = createComponent();
      const toggleButton = getToggleButton(component);

      await toggleButton.trigger('click');
      expect(getDocumentListItems(component).length).toBe(15);

      await toggleButton.trigger('click');
      expect(getDocumentListItems(component).length).toBe(12);
    });

    test('should update its conntent when clicked', async () => {
      const component = createComponent();
      const toggleButton = getToggleButton(component);

      expect(toggleButton.text()).toContain('Toon nog 3 documenten');

      await toggleButton.trigger('click');
      expect(toggleButton.text()).toContain('Verberg 3 documenten');
    });

    test('should not be available when there are no remaining documents', async () => {
      const component = createComponent(1);

      expect(getToggleButton(component).exists()).toBe(false);
    });
  });
});
