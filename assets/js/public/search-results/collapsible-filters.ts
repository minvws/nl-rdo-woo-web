import { collapseElement, expandElement } from '@js/utils';

export const collapsibleFilters = () => {
  let abortController: AbortController;

  const TOGGLE_BUTTON_COLLAPSED_CLASS = 'rotate-180';
  const TOGGLE_BUTTON_ANIMATION_CLASS_NAMES = [
    'transition-transform',
    'motion-reduce:transition-none',
  ];
  const GROUP_STORAGE_KEY = 'collapsed-search-filter-groups';
  const ITEMS_STORAGE_KEY = 'expanded-search-filter-items';

  const getFilterItemsCollapsibeElement = (filtersGroupElement: HTMLElement) =>
    filtersGroupElement.querySelector(
      '.js-filters-item-collapsible',
    ) as HTMLElement;

  const getFiltersGroupCollapsibeElement = (filtersGroupElement: HTMLElement) =>
    filtersGroupElement.querySelector(
      '.js-filters-group-collapsible',
    ) as HTMLElement;

  const getFiltersGroupKey = (filtersGroupElement: HTMLElement) =>
    filtersGroupElement.getAttribute('data-key') || '';

  const getToggleGroupButtonElement = (filtersGroupElement: HTMLElement) =>
    filtersGroupElement.querySelector(
      '.js-toggle-filters-group-button',
    ) as HTMLElement;

  const getToggleGroupButtonIconElement = (
    toggleGroupButtonElement: HTMLElement,
  ) =>
    toggleGroupButtonElement.querySelector(
      '.js-toggle-filters-group-button-icon',
    ) as HTMLElement;

  const getToggleItemsButtonElement = (filtersGroupElement: HTMLElement) =>
    filtersGroupElement.querySelector(
      '.js-toggle-filter-items-button',
    ) as HTMLElement;

  const initialize = () => {
    cleanup();

    abortController = new AbortController();
    (
      document.querySelectorAll('.js-filters-group') as NodeListOf<HTMLElement>
    ).forEach(initializeFiltersGroup);
  };

  const initializeFiltersGroup = (filtersGroupElement: HTMLElement) => {
    initializeToggleItems(filtersGroupElement);
    initializeToggleGroup(filtersGroupElement);
  };

  const initializeToggleGroup = (filtersGroupElement: HTMLElement) => {
    const toggleGroupButtonElement = getToggleGroupButtonElement(
      filtersGroupElement,
    ) as HTMLElement;
    if (!toggleGroupButtonElement) {
      return;
    }

    if (isFiltersGroupSavedAsCollapsed(filtersGroupElement)) {
      collapseFiltersGroup(filtersGroupElement, false);
    } else {
      expandFiltersGroup(filtersGroupElement, false);
    }

    setTimeout(() => {
      getToggleGroupButtonIconElement(toggleGroupButtonElement).classList.add(
        ...TOGGLE_BUTTON_ANIMATION_CLASS_NAMES,
      );
    }, 0);

    toggleGroupButtonElement.addEventListener(
      'click',
      () => {
        if (isToggleButtonMarkedAsCollapsed(toggleGroupButtonElement)) {
          expandFiltersGroup(filtersGroupElement);
          return;
        }
        collapseFiltersGroup(filtersGroupElement);
      },
      { signal: abortController.signal },
    );
  };

  const initializeToggleItems = (filtersGroupElement: HTMLElement) => {
    const toggleItemsButtonElement =
      getToggleItemsButtonElement(filtersGroupElement);
    if (!toggleItemsButtonElement) {
      return;
    }

    if (areFilterItemsSavedAsExpanded(filtersGroupElement)) {
      expandFilterItems(filtersGroupElement, false);
    } else {
      collapseFilterItems(filtersGroupElement, false);
    }

    toggleItemsButtonElement.addEventListener(
      'click',
      () => {
        if (isToggleButtonMarkedAsCollapsed(toggleItemsButtonElement)) {
          expandFilterItems(filtersGroupElement);
          return;
        }
        collapseFilterItems(filtersGroupElement);
      },
      { signal: abortController.signal },
    );
  };

  const collapseFiltersGroup = (
    filtersGroupElement: HTMLElement,
    withAnimation = true,
  ) => {
    const toggleButtonElement =
      getToggleGroupButtonElement(filtersGroupElement);

    markToggleButtonAs(toggleButtonElement, 'collapsed');

    const collapsibleElement =
      getFiltersGroupCollapsibeElement(filtersGroupElement);
    collapseElement(collapsibleElement, withAnimation);

    saveFiltersGroupAsCollapsed(filtersGroupElement);
  };

  const expandFiltersGroup = (
    filtersGroupElement: HTMLElement,
    withAnimation = true,
  ) => {
    const toggleButtonElement =
      getToggleGroupButtonElement(filtersGroupElement);

    markToggleButtonAs(toggleButtonElement, 'expanded');

    const collapsibleElement =
      getFiltersGroupCollapsibeElement(filtersGroupElement);
    expandElement(collapsibleElement, withAnimation);

    saveFiltersGroupAsExpanded(filtersGroupElement);
  };

  const collapseFilterItems = (
    filtersGroupElement: HTMLElement,
    withAnimation = true,
  ) => {
    const toggleButtonElement =
      getToggleItemsButtonElement(filtersGroupElement);

    const toState = 'collapsed';
    markToggleButtonAs(toggleButtonElement, toState);
    toggleElementText(toggleButtonElement, toState);

    const collapsibleElement =
      getFilterItemsCollapsibeElement(filtersGroupElement);
    collapseElement(collapsibleElement, withAnimation);

    saveFilterItemsAsCollapsed(filtersGroupElement);
  };

  const expandFilterItems = (
    filtersGroupElement: HTMLElement,
    withAnimation = true,
  ) => {
    const toggleButtonElement =
      getToggleItemsButtonElement(filtersGroupElement);

    const toState = 'expanded';
    markToggleButtonAs(toggleButtonElement, toState);
    toggleElementText(toggleButtonElement, toState);

    const collapsibleElement =
      getFilterItemsCollapsibeElement(filtersGroupElement);
    expandElement(collapsibleElement, withAnimation);

    saveFilterItemsAsExpanded(filtersGroupElement);
  };

  const markToggleButtonAs = (
    toggleButtonElement: HTMLElement,
    markAs: 'collapsed' | 'expanded',
  ) => {
    if (markAs === 'collapsed') {
      toggleButtonElement.setAttribute('aria-expanded', 'false');
      getToggleGroupButtonIconElement(toggleButtonElement)?.classList.add(
        TOGGLE_BUTTON_COLLAPSED_CLASS,
      );
      return;
    }

    toggleButtonElement.setAttribute('aria-expanded', 'true');
    getToggleGroupButtonIconElement(toggleButtonElement)?.classList.remove(
      TOGGLE_BUTTON_COLLAPSED_CLASS,
    );
  };

  const isToggleButtonMarkedAsCollapsed = (toggleButtonElement: HTMLElement) =>
    toggleButtonElement.getAttribute('aria-expanded') === 'false';

  const toggleElementText = (
    element: HTMLElement,
    state: 'collapsed' | 'expanded',
  ) => {
    const readFromAttribute =
      state === 'collapsed' ? 'data-text-collapsed' : 'data-text-expanded';
    element.textContent = element.getAttribute(readFromAttribute);
  };

  const saveFiltersGroupAsCollapsed = (filtersGroupElement: HTMLElement) => {
    const set = getCollapsedFiltersGroups();
    set.add(getFiltersGroupKey(filtersGroupElement));
    saveCollapsedFiltersGroups(set);
  };

  const saveFiltersGroupAsExpanded = (filtersGroupElement: HTMLElement) => {
    const set = getCollapsedFiltersGroups();
    set.delete(getFiltersGroupKey(filtersGroupElement));
    saveCollapsedFiltersGroups(set);
  };

  const saveFilterItemsAsCollapsed = (filtersGroupElement: HTMLElement) => {
    const set = getExpandedFilterItems();
    set.delete(getFiltersGroupKey(filtersGroupElement));
    saveExpandedFilterItems(set);
  };

  const saveFilterItemsAsExpanded = (filtersGroupElement: HTMLElement) => {
    const set = getExpandedFilterItems();
    set.add(getFiltersGroupKey(filtersGroupElement));
    saveExpandedFilterItems(set);
  };

  const areFilterItemsSavedAsExpanded = (filtersGroupElement: HTMLElement) => {
    const set = getExpandedFilterItems();
    return set.has(getFiltersGroupKey(filtersGroupElement));
  };

  const isFiltersGroupSavedAsCollapsed = (filtersGroupElement: HTMLElement) => {
    const set = getCollapsedFiltersGroups();
    return set.has(getFiltersGroupKey(filtersGroupElement));
  };

  const getCollapsedFiltersGroups = () =>
    getSetFromLocalStorage(GROUP_STORAGE_KEY);
  const saveCollapsedFiltersGroups = (set: Set<string>) =>
    saveSetInLocalStorage(GROUP_STORAGE_KEY, set);

  const getExpandedFilterItems = () =>
    getSetFromLocalStorage(ITEMS_STORAGE_KEY);
  const saveExpandedFilterItems = (set: Set<string>) =>
    saveSetInLocalStorage(ITEMS_STORAGE_KEY, set);

  const getSetFromLocalStorage = (storageKey: string) => {
    const savedFilterGroups = localStorage.getItem(storageKey);

    if (!savedFilterGroups) {
      return new Set<string>();
    }

    return new Set<string>(JSON.parse(savedFilterGroups));
  };

  const saveSetInLocalStorage = (storageKey: string, set: Set<string>) => {
    localStorage.setItem(storageKey, JSON.stringify([...set.values()]));
  };

  const cleanup = () => {
    if (abortController) {
      abortController.abort();
    }
  };

  return {
    cleanup,
    initialize,
  };
};
