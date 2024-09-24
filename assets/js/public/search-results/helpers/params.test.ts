import { beforeEach, describe, expect, test, vi } from 'vitest';
import { appendToParams, getSearchParams, getSearchParamsAndDelete, getSearchParamsAndSet, resetPageNumber } from './params';

const getMockedSearchQuery = () => '?mocked=true&also_mocked=yes';
const getMockedSearchParams = () => new URLSearchParams(getMockedSearchQuery());

let mockedWindow: { location: { search: string } };

vi.mock('../../../utils', () => ({
  getWindow: () => mockedWindow,
}));

beforeEach(() => {
  mockedWindow = {
    location: {
      search: getMockedSearchQuery(),
    },
  };
});

describe('The helper functions regarding adjusting search params', () => {
  describe('the "getSearchParamsAndDelete" function', () => {
    test('should delete the provided key from the current search params', () => {
      expect(getSearchParamsAndDelete('mocked').toString()).toBe(new URLSearchParams('also_mocked=yes').toString());
    });
  });

  describe('the "getSearchParamsAndSet" function', () => {
    test('should set the provided key and value to the current search params', () => {
      expect(getSearchParamsAndSet('also_mocked', 'no_but_yes').toString()).toBe(new URLSearchParams(
        '?mocked=true&also_mocked=no_but_yes',
      ).toString());
    });
  });

  describe('the "getSearchParams" function', () => {
    test('should return the current search params', () => {
      expect(getSearchParams().toString()).toBe(getMockedSearchParams().toString());
    });

    test('should rewrite some params created by PHP to make the params work in the browser', () => {
      mockedWindow = {
        location: {
          search: '?mock[0]=mocked_value&mock[1]=another_mocked_value',
        },
      };

      const searchParams = getSearchParams();

      expect(searchParams.has('mock[0]')).toBe(false);
      expect(searchParams.has('mock[1]')).toBe(false);

      expect(searchParams.getAll('mock[]')).toEqual(['mocked_value', 'another_mocked_value']);
    });
  });

  describe('the "appendToParams" function', () => {
    test('should append the provided value with the provided key', () => {
      const params = new URLSearchParams('?page=2&mock=true');

      expect(appendToParams(params, 'test', 'true').toString()).toBe(new URLSearchParams('?page=2&mock=true&test=true').toString());
    });
  });

  describe('the "resetPageNumber" function', () => {
    test('should remove the page number from the provided search params', () => {
      const params = new URLSearchParams('?page=2&mock=true');
      expect(resetPageNumber(params).toString()).toBe(new URLSearchParams('mock=true').toString());
    });
  });
});
