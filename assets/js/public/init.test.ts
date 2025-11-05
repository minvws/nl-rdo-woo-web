import {
  afterEach,
  beforeEach,
  describe,
  expect,
  MockInstance,
  test,
  vi,
} from 'vitest';
import { jsEnabled } from '@utils';
import { init } from './init';

let autoSubmitFormInitializeSpy: MockInstance;
let collapsiblesInitializeSpy: MockInstance;
let detailsComponentsInitializeSpy: MockInstance;
let mainNavInitializeSpy: MockInstance;
let searchResultsInitializeSpy: MockInstance;
let tabsInitializeSpy: MockInstance;

describe('the main "init" function for the admin', () => {
  vi.mock('@utils', async (importOriginal) => {
    const original = await importOriginal<typeof import('@utils')>();
    return {
      ...original,
      jsEnabled: vi.fn(),
    };
  });

  vi.mock('@js/shared', () => ({
    detailsComponents: () => ({
      initialize: detailsComponentsInitializeSpy,
    }),
    tabs: () => ({ initialize: tabsInitializeSpy }),
  }));

  vi.mock('./auto-submit-form', () => ({
    autoSubmitForm: () => ({
      initialize: autoSubmitFormInitializeSpy,
    }),
  }));

  vi.mock('./collapsibles', () => ({
    collapsibles: () => ({
      initialize: collapsiblesInitializeSpy,
    }),
  }));

  vi.mock('./main-nav', () => ({
    mainNav: () => ({
      initialize: mainNavInitializeSpy,
    }),
  }));

  vi.mock('./search-results', () => ({
    searchResults: () => ({
      initialize: searchResultsInitializeSpy,
    }),
  }));

  beforeEach(() => {
    autoSubmitFormInitializeSpy = vi.fn();
    collapsiblesInitializeSpy = vi.fn();
    detailsComponentsInitializeSpy = vi.fn();
    mainNavInitializeSpy = vi.fn();
    searchResultsInitializeSpy = vi.fn();
    tabsInitializeSpy = vi.fn();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  test('should add a class to the html element when javascript is enabled', () => {
    expect(jsEnabled).not.toHaveBeenCalled();

    init();
    expect(jsEnabled).toHaveBeenCalledOnce();
  });

  test('should initialize the functionalities used on the public side', () => {
    expect(autoSubmitFormInitializeSpy).not.toHaveBeenCalled();
    expect(collapsiblesInitializeSpy).not.toHaveBeenCalled();
    expect(detailsComponentsInitializeSpy).not.toHaveBeenCalled();
    expect(mainNavInitializeSpy).not.toHaveBeenCalled();
    expect(searchResultsInitializeSpy).not.toHaveBeenCalled();
    expect(tabsInitializeSpy).not.toHaveBeenCalled();

    init();
    expect(autoSubmitFormInitializeSpy).toHaveBeenCalled();
    expect(collapsiblesInitializeSpy).toHaveBeenCalled();
    expect(detailsComponentsInitializeSpy).toHaveBeenCalled();
    expect(mainNavInitializeSpy).toHaveBeenCalled();
    expect(searchResultsInitializeSpy).toHaveBeenCalled();
    expect(tabsInitializeSpy).toHaveBeenCalled();
  });
});
