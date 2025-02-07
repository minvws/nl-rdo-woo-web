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

let clickableRowInitializeSpy: MockInstance;
let clickOnSelectorInitializeSpy: MockInstance;
let copyToClipboardInitializeSpy: MockInstance;
let detailsComponentsInitializeSpy: MockInstance;
let manageWidgetInitializeSpy: MockInstance;
let printPageInitializeSpy: MockInstance;
let sortTablesInitializeSpy: MockInstance;
let tabsInitializeSpy: MockInstance;
let toggleDialogInitializeSpy: MockInstance;
let uploadAreasInitializeSpy: MockInstance;
let visibilityTogglerInitializeSpy: MockInstance;

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
    tabs: () => ({
      initialize: tabsInitializeSpy,
    }),
  }));

  vi.mock('./clickable-row', () => ({
    clickableRows: () => ({
      initialize: clickableRowInitializeSpy,
    }),
  }));

  vi.mock('./click-on-selector', () => ({
    clickOnSelector: () => ({
      initialize: clickOnSelectorInitializeSpy,
    }),
  }));

  vi.mock('./copy-to-clipboard', () => ({
    copyToClipboard: () => ({
      initialize: copyToClipboardInitializeSpy,
    }),
  }));

  vi.mock('./dialog', () => ({
    toggleDialog: () => ({
      initialize: toggleDialogInitializeSpy,
    }),
  }));

  vi.mock('./manage-widget', () => ({
    manageWidget: () => ({
      initialize: manageWidgetInitializeSpy,
    }),
  }));

  vi.mock('./print', () => ({
    printPage: () => ({
      initialize: printPageInitializeSpy,
    }),
  }));

  vi.mock('./sort-tables', () => ({
    sortTables: () => ({
      initialize: sortTablesInitializeSpy,
    }),
  }));

  vi.mock('./upload-areas', () => ({
    uploadAreas: () => ({
      initialize: uploadAreasInitializeSpy,
    }),
  }));

  vi.mock('./visibility-toggler', () => ({
    visibilityToggler: () => ({
      initialize: visibilityTogglerInitializeSpy,
    }),
  }));

  beforeEach(() => {
    clickableRowInitializeSpy = vi.fn();
    clickOnSelectorInitializeSpy = vi.fn();
    copyToClipboardInitializeSpy = vi.fn();
    detailsComponentsInitializeSpy = vi.fn();
    manageWidgetInitializeSpy = vi.fn();
    printPageInitializeSpy = vi.fn();
    sortTablesInitializeSpy = vi.fn();
    tabsInitializeSpy = vi.fn();
    toggleDialogInitializeSpy = vi.fn();
    uploadAreasInitializeSpy = vi.fn();
    visibilityTogglerInitializeSpy = vi.fn();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  test('should add a class to the html element when javascript is enabled', () => {
    expect(jsEnabled).not.toHaveBeenCalled();

    init();
    expect(jsEnabled).toHaveBeenCalledOnce();
  });

  test('should initialize the functionalities used in the admin', () => {
    expect(clickableRowInitializeSpy).not.toHaveBeenCalled();
    expect(clickOnSelectorInitializeSpy).not.toHaveBeenCalled();
    expect(copyToClipboardInitializeSpy).not.toHaveBeenCalled();
    expect(detailsComponentsInitializeSpy).not.toHaveBeenCalled();
    expect(manageWidgetInitializeSpy).not.toHaveBeenCalled();
    expect(printPageInitializeSpy).not.toHaveBeenCalled();
    expect(sortTablesInitializeSpy).not.toHaveBeenCalled();
    expect(tabsInitializeSpy).not.toHaveBeenCalled();
    expect(toggleDialogInitializeSpy).not.toHaveBeenCalled();
    expect(uploadAreasInitializeSpy).not.toHaveBeenCalled();
    expect(visibilityTogglerInitializeSpy).not.toHaveBeenCalled();

    init();
    expect(clickableRowInitializeSpy).toHaveBeenCalled();
    expect(clickOnSelectorInitializeSpy).toHaveBeenCalled();
    expect(copyToClipboardInitializeSpy).toHaveBeenCalled();
    expect(detailsComponentsInitializeSpy).toHaveBeenCalled();
    expect(manageWidgetInitializeSpy).toHaveBeenCalled();
    expect(printPageInitializeSpy).toHaveBeenCalled();
    expect(sortTablesInitializeSpy).toHaveBeenCalled();
    expect(tabsInitializeSpy).toHaveBeenCalled();
    expect(toggleDialogInitializeSpy).toHaveBeenCalled();
    expect(uploadAreasInitializeSpy).toHaveBeenCalled();
    expect(visibilityTogglerInitializeSpy).toHaveBeenCalled();
  });
});
