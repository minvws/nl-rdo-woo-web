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

let autoSubmitFormCleanupSpy: MockInstance;
let autoSubmitFormInitializeSpy: MockInstance;
let detailsComponentsCleanupSpy: MockInstance;
let detailsComponentsInitializeSpy: MockInstance;
let mainNavCleanupSpy: MockInstance;
let mainNavInitializeSpy: MockInstance;
let searchResultsCleanupSpy: MockInstance;
let searchResultsInitializeSpy: MockInstance;
let tabsCleanupSpy: MockInstance;
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
      cleanup: detailsComponentsCleanupSpy,
      initialize: detailsComponentsInitializeSpy,
    }),
  }));

  vi.mock('./auto-submit-form', () => ({
    autoSubmitForm: () => ({
      cleanup: autoSubmitFormCleanupSpy,
      initialize: autoSubmitFormInitializeSpy,
    }),
  }));

  vi.mock('./main-nav', () => ({
    mainNav: () => ({
      cleanup: mainNavCleanupSpy,
      initialize: mainNavInitializeSpy,
    }),
  }));

  vi.mock('./search-results', () => ({
    searchResults: () => ({
      cleanup: searchResultsCleanupSpy,
      initialize: searchResultsInitializeSpy,
    }),
  }));

  vi.mock('./tabs', () => ({
    tabs: () => ({ cleanup: tabsCleanupSpy, initialize: tabsInitializeSpy }),
  }));

  beforeEach(() => {
    autoSubmitFormCleanupSpy = vi.fn();
    autoSubmitFormInitializeSpy = vi.fn();
    detailsComponentsCleanupSpy = vi.fn();
    detailsComponentsInitializeSpy = vi.fn();
    mainNavCleanupSpy = vi.fn();
    mainNavInitializeSpy = vi.fn();
    searchResultsCleanupSpy = vi.fn();
    searchResultsInitializeSpy = vi.fn();
    tabsCleanupSpy = vi.fn();
    tabsInitializeSpy = vi.fn();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  const triggerBeforeUnload = () => {
    window.dispatchEvent(new Event('beforeunload'));
  };

  test('should add a class to the html element when javascript is enabled', () => {
    expect(jsEnabled).not.toHaveBeenCalled();

    init();
    expect(jsEnabled).toHaveBeenCalledOnce();
  });

  test('should initialize the functionalities used on the public side', () => {
    expect(autoSubmitFormInitializeSpy).not.toHaveBeenCalled();
    expect(detailsComponentsInitializeSpy).not.toHaveBeenCalled();
    expect(mainNavInitializeSpy).not.toHaveBeenCalled();
    expect(searchResultsInitializeSpy).not.toHaveBeenCalled();
    expect(tabsInitializeSpy).not.toHaveBeenCalled();

    init();
    expect(autoSubmitFormInitializeSpy).toHaveBeenCalled();
    expect(detailsComponentsInitializeSpy).toHaveBeenCalled();
    expect(mainNavInitializeSpy).toHaveBeenCalled();
    expect(searchResultsInitializeSpy).toHaveBeenCalled();
    expect(tabsInitializeSpy).toHaveBeenCalled();
  });

  test('should clean up the functionalities when the beforeunload event is triggered', () => {
    init();
    expect(autoSubmitFormCleanupSpy).not.toHaveBeenCalled();
    expect(detailsComponentsCleanupSpy).not.toHaveBeenCalled();
    expect(mainNavCleanupSpy).not.toHaveBeenCalled();
    expect(searchResultsCleanupSpy).not.toHaveBeenCalled();
    expect(tabsCleanupSpy).not.toHaveBeenCalled();

    triggerBeforeUnload();
    expect(autoSubmitFormCleanupSpy).toHaveBeenCalled();
    expect(detailsComponentsCleanupSpy).toHaveBeenCalled();
    expect(mainNavCleanupSpy).toHaveBeenCalled();
    expect(searchResultsCleanupSpy).toHaveBeenCalled();
    expect(tabsCleanupSpy).toHaveBeenCalled();
  });
});
