import { InputErrorId } from '@admin-fe/form';
import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import RemovableInput from './RemovableInput.vue';

describe('The "<InputErrors />" component', () => {
  const mockedErrors = [
    {
      id: InputErrorId.Required,
    },
  ];

  interface Props {
    canDelete: boolean;
    areErrorsVisible: boolean;
  }

  const createComponent = (props: Partial<Props> = {}) =>
    mount(RemovableInput, {
      props: {
        areErrorsVisible: false,
        canDelete: props.canDelete ?? false,
        errors: mockedErrors,
        id: 'mocked-id',
        label: 'mocked-label',
      },
      slots: {
        default: 'Mocked provided content',
      },
      shallow: true,
    });

  const getInputErrorsComponent = (component: VueWrapper) =>
    component.findComponent({
      name: 'InputErrors',
    });

  const getDeleteButton = (component: VueWrapper) => component.find('button');

  test('should display the provided label', () => {
    const labelElement = createComponent().find('label');
    expect(labelElement.text()).toBe('mocked-label');
    expect(labelElement.attributes('for')).toBe('mocked-id');
  });

  test('should display the provided content', () => {
    expect(createComponent().text()).toContain('Mocked provided content');
  });

  test('should display the provided errors if they should be visible', async () => {
    const component = createComponent();
    expect(getInputErrorsComponent(component).exists()).toBe(false);

    await component.setProps({
      areErrorsVisible: true,
    });

    expect(getInputErrorsComponent(component).props()).toMatchObject({
      errors: mockedErrors,
      inputId: 'mocked-id',
      validatorMessages: {
        forbidden: expect.any(Function),
      },
    });
  });

  describe('the delete button', () => {
    test('should only be visible if the "canDelete" prop is true', async () => {
      const component = createComponent();
      expect(getDeleteButton(component).exists()).toBe(false);

      await component.setProps({
        canDelete: true,
      });
      expect(getDeleteButton(component).exists()).toBe(true);
    });

    test('should emit a "delete" event when clicked', async () => {
      const component = createComponent({ canDelete: true });
      expect(component.emitted('delete')).toBeUndefined();

      getDeleteButton(component).trigger('click');
      expect(component.emitted('delete')).toBeTruthy();
    });
  });
});
