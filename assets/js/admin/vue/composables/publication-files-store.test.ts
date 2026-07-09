import { describe, expect, it } from 'vitest';
import { usePublicationFilesStore } from './publication-files-store';

describe('the "usePublicationFilesStore" composable', () => {
  it('should have a property which indicates if a main document is set and is "false" by default', () => {
    const store = usePublicationFilesStore();

    expect(store.state.hasMainDocument).toBe(false);
  });

  it('should have a property which indicates if a notice not public is set and is "false" by default', () => {
    const store = usePublicationFilesStore();

    expect(store.state.hasNotice).toBe(false);
  });

  it('should allow to update the "hasMainDocument" property', () => {
    const store = usePublicationFilesStore();

    store.setHasMainDocument(true);
    expect(store.state.hasMainDocument).toBe(true);

    store.setHasMainDocument(false);
    expect(store.state.hasMainDocument).toBe(false);
  });

  it('should allow to update the "hasNotice" property', () => {
    const store = usePublicationFilesStore();

    store.setHasNotice(true);
    expect(store.state.hasNotice).toBe(true);

    store.setHasNotice(false);
    expect(store.state.hasNotice).toBe(false);
  });

  it('should share state across separate calls', () => {
    const storeA = usePublicationFilesStore();
    const storeB = usePublicationFilesStore();

    storeA.setHasMainDocument(true);
    storeB.setHasNotice(true);

    expect(storeB.state.hasMainDocument).toBe(true);
    expect(storeA.state.hasNotice).toBe(true);
  });
});
