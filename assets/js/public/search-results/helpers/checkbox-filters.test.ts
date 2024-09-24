import { beforeEach, describe, expect, test, vi } from 'vitest';
import {
  getCheckboxFilterElements, getNamesAndValues, getUpdatedParamsFromCheckboxFilter, queryCheckboxFilterElements,
} from './checkbox-filters';

let mockedSearchParams: URLSearchParams;

vi.mock('./params', async (importOriginal) => {
  const original = await importOriginal<typeof import('./params')>();
  return {
    ...original,
    getSearchParams: vi.fn().mockImplementation(() => mockedSearchParams),
  };
});

describe('The helper functions regarding checkbox filters', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <div class="js-search-filter-checkbox-group">
        <input
          class="js-search-filter-checkbox" type="checkbox" name="with_sublevel_name" value="with_sublevel_value" data-has-sublevel="true"
        >
        <div id="some-id">
          <input class="js-search-filter-checkbox" type="checkbox" name="sublevel_item_1_name" value="sublevel_item_1_value">
          <input class="js-search-filter-checkbox" type="checkbox" name="sublevel_item_2_name" value="sublevel_item_2_value">
        </div>
      </div>
      <input class="js-search-filter-checkbox" type="checkbox" name="without_sublevel_name" value="without_sublevel_value">
    `;

    mockedSearchParams = new URLSearchParams();
  });

  const getCheckboxElementByName = (name: string) => document.querySelector(`input[name="${name}"]`) as HTMLInputElement;

  describe('the "getCheckboxFilterElements" function', () => {
    test('should retrieve all checkbox filter elements available', () => {
      expect(getCheckboxFilterElements().length).toBe(4);
    });
  });

  describe('the "queryCheckboxFilterElements" function', () => {
    test('should retrieve all checkbox filter within the provided element', () => {
      expect(queryCheckboxFilterElements(document.getElementById('none-existing-id')).length).toBe(0);
      expect(queryCheckboxFilterElements(document.getElementById('some-id')).length).toBe(2);
    });
  });

  describe('the "getNamesAndValues" function', () => {
    test('should retrieve the name and value for all checkboxes within a group', () => {
      const expected = [
        { name: 'with_sublevel_name', value: 'with_sublevel_value' },
        { name: 'sublevel_item_1_name', value: 'sublevel_item_1_value' },
        { name: 'sublevel_item_2_name', value: 'sublevel_item_2_value' },
      ];
      expect(getNamesAndValues(getCheckboxElementByName('with_sublevel_name'))).toEqual(expected);
    });

    test('should retrieve the name and value for the provided checkbox if it is not grouped', () => {
      const expected = [{ name: 'sublevel_item_1_name', value: 'sublevel_item_1_value' }];
      expect(getNamesAndValues(getCheckboxElementByName('sublevel_item_1_name'))).toEqual(expected);
    });
  });

  describe('the "getUpdatedParamsFromCheckboxFilter" function', () => {
    test('should return the current search params if no checkbox element is provided', () => {
      expect(getUpdatedParamsFromCheckboxFilter(undefined)).toEqual(new URLSearchParams());
    });

    test('should append the names and values for all checkboxes in a group to the current url', () => {
      const expected = new URLSearchParams();
      expected.append('with_sublevel_name', 'with_sublevel_value');
      expected.append('sublevel_item_1_name', 'sublevel_item_1_value');
      expected.append('sublevel_item_2_name', 'sublevel_item_2_value');

      expect(getUpdatedParamsFromCheckboxFilter(getCheckboxElementByName('with_sublevel_name'), true)).toEqual(expected);
    });

    test('should remove the names and values for all checkboxes in a group to the current url', () => {
      mockedSearchParams = new URLSearchParams('?mocked=true');
      mockedSearchParams.append('with_sublevel_name', 'with_sublevel_value');
      mockedSearchParams.append('sublevel_item_1_name', 'sublevel_item_1_value');
      mockedSearchParams.append('sublevel_item_2_name', 'sublevel_item_2_value');

      expect(getUpdatedParamsFromCheckboxFilter(getCheckboxElementByName('with_sublevel_name'), false).toString()).toEqual(
        new URLSearchParams('?mocked=true').toString(),
      );
    });

    test('should remove the name and value from the top level checkbox if it is the only one in a group which is selected', () => {
      mockedSearchParams = new URLSearchParams('?mocked=true');
      mockedSearchParams.append('with_sublevel_name', 'with_sublevel_value');
      mockedSearchParams.append('sublevel_item_1_name', 'sublevel_item_1_value');

      expect(getUpdatedParamsFromCheckboxFilter(getCheckboxElementByName('sublevel_item_1_name'), false).toString()).toEqual(
        new URLSearchParams('?mocked=true').toString(),
      );
    });
  });
});
