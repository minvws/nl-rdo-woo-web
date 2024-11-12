import {
  MockInstance,
  afterEach,
  beforeEach,
  describe,
  expect,
  test,
  vi,
} from 'vitest';
import { dossierSearch } from './search';

let searchPreviewsCleanupSpy: MockInstance;
let searchPreviewsInitializeSpy: MockInstance;

describe('The "dossierSearch" function', () => {
  vi.mock('./search-previews', () => ({
    searchPreviews: () => ({
      cleanup: searchPreviewsCleanupSpy,
      initialize: searchPreviewsInitializeSpy,
    }),
  }));

  let cleanup: () => void;
  let initialize: () => void;

  beforeEach(() => {
    searchPreviewsCleanupSpy = vi.fn();
    searchPreviewsInitializeSpy = vi.fn();

    ({ cleanup, initialize } = dossierSearch());
    initialize();
  });

  afterEach(() => {
    cleanup();
  });

  test('initializes the search previews', () => {
    expect(searchPreviewsInitializeSpy).toHaveBeenNthCalledWith(
      1,
      'js-dossier-search-previews',
    );
  });

  test('cleans up the search previews when cleaning up the dossier search functionality', () => {
    expect(searchPreviewsCleanupSpy).not.toHaveBeenCalled();

    cleanup();
    expect(searchPreviewsCleanupSpy).toHaveBeenCalledOnce();
  });
});
