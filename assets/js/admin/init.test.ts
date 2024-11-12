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

let clickOnSelectorCleanupSpy: MockInstance;
let clickOnSelectorInitializeSpy: MockInstance;
let copyToClipboardCleanupSpy: MockInstance;
let copyToClipboardInitializeSpy: MockInstance;
let detailsComponentsCleanupSpy: MockInstance;
let detailsComponentsInitializeSpy: MockInstance;
let manageWidgetCleanupSpy: MockInstance;
let manageWidgetInitializeSpy: MockInstance;
let printPageCleanupSpy: MockInstance;
let printPageInitializeSpy: MockInstance;
let sortTablesCleanupSpy: MockInstance;
let sortTablesInitializeSpy: MockInstance;
let toggleDialogCleanupSpy: MockInstance;
let toggleDialogInitializeSpy: MockInstance;
let uploadAreasCleanupSpy: MockInstance;
let uploadAreasInitializeSpy: MockInstance;
let visibilityTogglerCleanupSpy: MockInstance;
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
      cleanup: detailsComponentsCleanupSpy,
      initialize: detailsComponentsInitializeSpy,
    }),
  }));

  vi.mock('./click-on-selector', () => ({
    clickOnSelector: () => ({
      cleanup: clickOnSelectorCleanupSpy,
      initialize: clickOnSelectorInitializeSpy,
    }),
  }));

  vi.mock('./copy-to-clipboard', () => ({
    copyToClipboard: () => ({
      cleanup: copyToClipboardCleanupSpy,
      initialize: copyToClipboardInitializeSpy,
    }),
  }));

  vi.mock('./dialog', () => ({
    toggleDialog: () => ({
      cleanup: toggleDialogCleanupSpy,
      initialize: toggleDialogInitializeSpy,
    }),
  }));

  vi.mock('./manage-widget', () => ({
    manageWidget: () => ({
      cleanup: manageWidgetCleanupSpy,
      initialize: manageWidgetInitializeSpy,
    }),
  }));

  vi.mock('./print', () => ({
    printPage: () => ({
      cleanup: printPageCleanupSpy,
      initialize: printPageInitializeSpy,
    }),
  }));

  vi.mock('./sort-tables', () => ({
    sortTables: () => ({
      cleanup: sortTablesCleanupSpy,
      initialize: sortTablesInitializeSpy,
    }),
  }));

  vi.mock('./upload-areas', () => ({
    uploadAreas: () => ({
      cleanup: uploadAreasCleanupSpy,
      initialize: uploadAreasInitializeSpy,
    }),
  }));

  vi.mock('./visibility-toggler', () => ({
    visibilityToggler: () => ({
      cleanup: visibilityTogglerCleanupSpy,
      initialize: visibilityTogglerInitializeSpy,
    }),
  }));

  beforeEach(() => {
    clickOnSelectorCleanupSpy = vi.fn();
    clickOnSelectorInitializeSpy = vi.fn();
    copyToClipboardCleanupSpy = vi.fn();
    copyToClipboardInitializeSpy = vi.fn();
    detailsComponentsCleanupSpy = vi.fn();
    detailsComponentsInitializeSpy = vi.fn();
    manageWidgetCleanupSpy = vi.fn();
    manageWidgetInitializeSpy = vi.fn();
    printPageCleanupSpy = vi.fn();
    printPageInitializeSpy = vi.fn();
    sortTablesCleanupSpy = vi.fn();
    sortTablesInitializeSpy = vi.fn();
    toggleDialogCleanupSpy = vi.fn();
    toggleDialogInitializeSpy = vi.fn();
    uploadAreasCleanupSpy = vi.fn();
    uploadAreasInitializeSpy = vi.fn();
    visibilityTogglerCleanupSpy = vi.fn();
    visibilityTogglerInitializeSpy = vi.fn();
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

  test('should initialize the functionalities used in the admin', () => {
    expect(clickOnSelectorInitializeSpy).not.toHaveBeenCalled();
    expect(copyToClipboardInitializeSpy).not.toHaveBeenCalled();
    expect(detailsComponentsInitializeSpy).not.toHaveBeenCalled();
    expect(manageWidgetInitializeSpy).not.toHaveBeenCalled();
    expect(printPageInitializeSpy).not.toHaveBeenCalled();
    expect(sortTablesInitializeSpy).not.toHaveBeenCalled();
    expect(toggleDialogInitializeSpy).not.toHaveBeenCalled();
    expect(uploadAreasInitializeSpy).not.toHaveBeenCalled();
    expect(visibilityTogglerInitializeSpy).not.toHaveBeenCalled();

    init();
    expect(clickOnSelectorInitializeSpy).toHaveBeenCalled();
    expect(copyToClipboardInitializeSpy).toHaveBeenCalled();
    expect(detailsComponentsInitializeSpy).toHaveBeenCalled();
    expect(manageWidgetInitializeSpy).toHaveBeenCalled();
    expect(printPageInitializeSpy).toHaveBeenCalled();
    expect(sortTablesInitializeSpy).toHaveBeenCalled();
    expect(toggleDialogInitializeSpy).toHaveBeenCalled();
    expect(uploadAreasInitializeSpy).toHaveBeenCalled();
    expect(visibilityTogglerInitializeSpy).toHaveBeenCalled();
  });

  test('should clean up the functionalities when the beforeunload event is triggered', () => {
    init();
    expect(clickOnSelectorCleanupSpy).not.toHaveBeenCalled();
    expect(copyToClipboardCleanupSpy).not.toHaveBeenCalled();
    expect(detailsComponentsCleanupSpy).not.toHaveBeenCalled();
    expect(manageWidgetCleanupSpy).not.toHaveBeenCalled();
    expect(printPageCleanupSpy).not.toHaveBeenCalled();
    expect(sortTablesCleanupSpy).not.toHaveBeenCalled();
    expect(toggleDialogCleanupSpy).not.toHaveBeenCalled();
    expect(uploadAreasCleanupSpy).not.toHaveBeenCalled();
    expect(visibilityTogglerCleanupSpy).not.toHaveBeenCalled();

    triggerBeforeUnload();
    expect(clickOnSelectorCleanupSpy).toHaveBeenCalled();
    expect(copyToClipboardCleanupSpy).toHaveBeenCalled();
    expect(detailsComponentsCleanupSpy).toHaveBeenCalled();
    expect(manageWidgetCleanupSpy).toHaveBeenCalled();
    expect(printPageCleanupSpy).toHaveBeenCalled();
    expect(sortTablesCleanupSpy).toHaveBeenCalled();
    expect(toggleDialogCleanupSpy).toHaveBeenCalled();
    expect(uploadAreasCleanupSpy).toHaveBeenCalled();
    expect(visibilityTogglerCleanupSpy).toHaveBeenCalled();
  });
});
