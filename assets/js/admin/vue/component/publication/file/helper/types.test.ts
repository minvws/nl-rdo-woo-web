import { createMockedAttachmentTypes } from '@js/test';
import { describe, expect, it } from 'vitest';
import { PublicationFileTypes } from '../interface';
import {
  collectAllSelectOptionsFromFileTypes,
  findFileTypeLabelByValue,
  getOptgroupsFromFileTypes,
  getSelectOptionsFromFileTypes,
  getValuesFromPublicationFileTypes,
} from './types';

describe('The helper functions regarding document types', () => {
  const getMockedPublicationFileTypes = (): PublicationFileTypes => [
    {
      type: 'AttachmentTypeBranch',
      label: 'label_1',
      subbranch: null,
      attachmentTypes: createMockedAttachmentTypes('label_1', 1),
    },
    {
      type: 'AttachmentTypeBranch',
      label: 'label_2',
      subbranch: {
        type: 'AttachmentTypeBranch',
        label: 'label_2.sub_1',
        subbranch: null,
        attachmentTypes: createMockedAttachmentTypes('label_2.sub_1', 2),
      },
      attachmentTypes: createMockedAttachmentTypes('label_2', 3),
    },
    ...createMockedAttachmentTypes('label_3', 1),
  ];

  describe('the function to collect all select options', () => {
    it('should return a list of select options', () => {
      const selectOptions = collectAllSelectOptionsFromFileTypes(
        getMockedPublicationFileTypes(),
      );

      expect(selectOptions).toHaveLength(7);
      expect(selectOptions).toContainEqual({
        label: 'label_1.label_1',
        value: 'label_1.value_1',
      });
      expect(selectOptions).toContainEqual({
        label: 'label_2.sub_1.label_2',
        value: 'label_2.sub_1.value_2',
      });
      expect(selectOptions).toContainEqual({
        label: 'label_2.label_3',
        value: 'label_2.value_3',
      });
      expect(selectOptions).toContainEqual({
        label: 'label_3.label_1',
        value: 'label_3.value_1',
      });
    });
  });

  describe('the function to get the select options from the document types', () => {
    it('should return the select options of the document types', () => {
      expect(
        getSelectOptionsFromFileTypes(getMockedPublicationFileTypes()),
      ).toEqual([{ label: 'label_3.label_1', value: 'label_3.value_1' }]);
    });
  });

  describe('the function to find the label by value', () => {
    it('should return the label of the value', () => {
      const label = findFileTypeLabelByValue(
        getMockedPublicationFileTypes(),
        'label_2.sub_1.value_2',
      );
      expect(label).toBe('Label_2.sub_1.label_2');
    });
  });

  describe('the function to get the optgroups from the document types', () => {
    it('should return the optgroups of the document types', () => {
      expect(
        getOptgroupsFromFileTypes(getMockedPublicationFileTypes()),
      ).toMatchObject([
        {
          label: 'label_1',
          options: [{ label: 'label_1.label_1', value: 'label_1.value_1' }],
        },
        {
          label: 'label_2',
          options: [
            { label: 'label_2.label_1', value: 'label_2.value_1' },
            { label: 'label_2.label_2', value: 'label_2.value_2' },
            { label: 'label_2.label_3', value: 'label_2.value_3' },
          ],
        },
        {
          label: 'label_2.sub_1',
          options: [
            { label: 'label_2.sub_1.label_1', value: 'label_2.sub_1.value_1' },
            { label: 'label_2.sub_1.label_2', value: 'label_2.sub_1.value_2' },
          ],
        },
      ]);
    });
  });

  describe('the function to get the values from the document types', () => {
    it('should return the values of the document types', () => {
      const values = getValuesFromPublicationFileTypes(
        getMockedPublicationFileTypes(),
      );
      expect(values).toHaveLength(7);
      expect(values).toEqual(
        expect.arrayContaining([
          'label_1.value_1',
          'label_2.value_1',
          'label_2.value_2',
          'label_2.value_3',
          'label_2.sub_1.value_1',
          'label_2.sub_1.value_2',
          'label_3.value_1',
        ]),
      );
    });
  });
});
