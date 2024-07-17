import InputDocumentTypes from '@admin-fe/component/publication/InputDocumentTypes.vue';
import {
  getOptgroupsFromDocumentTypes,
  getSelectOptionsFromDocumentTypes,
  type DocumentTypes,
} from '@admin-fe/component/publication/helper';
import { mount } from '@vue/test-utils';
import { MockInstance, describe, expect, test, vi } from 'vitest';

describe('The "InputDocumentTypes" component', () => {
  const getMockedOptions = (): DocumentTypes => ({
    1: {
      type: 'AttachmentTypeBranch',
      label: 'adviesdocument',
      subbranch: null,
      attachmentTypes: [
        {
          type: 'AttachmentType',
          value: 'c_d506b718',
          label: 'advies',
        },
        {
          type: 'AttachmentType',
          value: 'c_a40458df',
          label: 'adviesaanvraag',
        },
        {
          type: 'AttachmentType',
          value: 'c_0e425c23',
          label: 'adviesvoorstel',
        },
      ],
    },
    2: {
      type: 'AttachmentTypeBranch',
      label: 'beleidsdocument',
      subbranch: {
        type: 'AttachmentTypeBranch',
        label: 'rapport',
        subbranch: null,
        attachmentTypes: [
          {
            type: 'AttachmentType',
            value: 'c_8b92eab4',
            label: 'ambtsbericht',
          },
          {
            type: 'AttachmentType',
            value: 'c_38ba44de',
            label: 'evaluatierapport',
          },
        ],
      },
      attachmentTypes: [
        {
          type: 'AttachmentType',
          value: 'c_dfa0ff1f',
          label: 'begroting',
        },
        {
          type: 'AttachmentType',
          value: 'c_9376c730',
          label: 'beleidsnota',
        },
      ],
    },
  });

  const createComponent = (options: DocumentTypes, addInputSpy: MockInstance) => mount(InputDocumentTypes, {
    global: {
      provide: {
        form: {
          addInput: addInputSpy,
        },
      },
    },
    props: {
      options,
      value: 'mocked-value',
    },
    shallow: true,
  });

  const getInputSelectComponent = (options = getMockedOptions(), addInputSpy = vi.fn()) => createComponent(
    options,
    addInputSpy,
  ).findComponent({ name: 'InputSelect' });

  describe('when the component is provided with multiple options', () => {
    test('should display a dropdown with a label being "Soort document"', () => {
      expect(getInputSelectComponent().props('label')).toBe('Soort document');
    });

    test('should display a dropdown with a name being "type"', () => {
      expect(getInputSelectComponent().props('name')).toBe('type');
    });

    test('should display a dropdown with a help text being "Wat is de rol van dit document in het Woo-proces?"', () => {
      expect(getInputSelectComponent().props('helpText')).toBe('Wat is de rol van dit document in het Woo-proces?');
    });

    test('should transform the provided options into dropdown suitable options and pass them to the dropdown component', () => {
      expect(getInputSelectComponent().props('options')).toEqual(getSelectOptionsFromDocumentTypes(getMockedOptions()));
    });

    test('should transform the provided options into dropdown suitable opt groups and pass them to the dropdown component', () => {
      expect(getInputSelectComponent().props('optgroups')).toEqual(getOptgroupsFromDocumentTypes(getMockedOptions()));
    });
  });

  describe('when the component is provided with one option or less', () => {
    const oneMockedOption: DocumentTypes = {
      1: {
        type: 'AttachmentType',
        value: 'mocked-only-option-value',
        label: 'mocked-only-option-label',
      },
    };

    test('should not display a dropdown', () => {
      expect(getInputSelectComponent(oneMockedOption).exists()).toBe(false);
    });

    test('should register a value which equals the value of the only provided option', () => {
      const addInputSpy = vi.fn();

      getInputSelectComponent(oneMockedOption, addInputSpy);
      expect(addInputSpy).toHaveBeenNthCalledWith(1, expect.objectContaining({
        name: 'type',
        label: 'Soort document',
        value: 'mocked-only-option-value',
      }));
    });
  });
});
