import { mount, VueWrapper } from '@vue/test-utils';
import { beforeEach, describe, expect, it, Mock, vi } from 'vitest';
import Dialog from './Dialog.vue';

describe('The <Dialog /> component', () => {
  let closeDialogSpy: Mock;
  let showDialogSpy: Mock;

  const createComponent = () =>
    mount(Dialog, {
      props: {
        title: 'mocked-title',
      },
      slots: {
        default: 'This is the mocked content',
      },
    });

  const getDialogElement = (component: VueWrapper) => component.find('dialog');
  const getTitleElement = (component: VueWrapper) =>
    getDialogElement(component).find('h1');
  const getCloseButtonElement = (component: VueWrapper) =>
    getDialogElement(component).find('form button');

  const openDialog = async (component: VueWrapper) => {
    await component.setProps({ modelValue: true });
  };

  const closeDialog = async (component: VueWrapper) => {
    await component.setProps({ modelValue: false });
  };

  beforeEach(() => {
    closeDialogSpy = vi.fn();
    showDialogSpy = vi.fn();

    HTMLDialogElement.prototype.close = closeDialogSpy;
    HTMLDialogElement.prototype.showModal = showDialogSpy;
  });

  it('should display the provided title in an h1 element', () => {
    expect(getTitleElement(createComponent()).text()).toContain('mocked-title');
  });

  it('should label the dialog with the title', () => {
    const component = createComponent();
    expect(getDialogElement(component).attributes('aria-labelledby')).toBe(
      getTitleElement(component).attributes('id'),
    );
  });

  it('should display a button within a form with a close icon which closes the dialog element', async () => {
    const closeButton = getCloseButtonElement(createComponent());
    const closeButtonIcon = closeButton.findComponent({ name: 'Icon' });

    expect(closeButtonIcon.exists()).toBe(true);
    expect(closeButtonIcon.props('name')).toBe('cross');
  });

  it('should display the provided content', () => {
    const component = createComponent();
    expect(component.text()).toContain('This is the mocked content');
  });

  it('should open the dialog element when opening the provided model value changes to "true"', async () => {
    const component = createComponent();

    expect(showDialogSpy).not.toHaveBeenCalled();

    await openDialog(component);
    expect(showDialogSpy).toHaveBeenCalled();
  });

  it('should close the dialog element when the provided model value changes to "false"', async () => {
    const component = createComponent();

    await openDialog(component);
    expect(closeDialogSpy).not.toHaveBeenCalled();

    await closeDialog(component);
    expect(closeDialogSpy).toHaveBeenCalled();
  });

  it('should close the dialog element when dialog element receives a close event', async () => {
    const component = createComponent();

    await openDialog(component);
    expect(closeDialogSpy).not.toHaveBeenCalled();

    await getDialogElement(component).trigger('close');
    expect(closeDialogSpy).toHaveBeenCalled();
  });

  it('should emit a close event when the dialog element receives a close event', async () => {
    const component = createComponent();

    await openDialog(component);
    expect(component.emitted('close')).toBeUndefined();

    await getDialogElement(component).trigger('close');
    expect(component.emitted('close')).toBeDefined();
  });
});
