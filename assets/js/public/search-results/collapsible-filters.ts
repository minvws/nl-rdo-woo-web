export const collapsibleFilters = () => {
  let abortController: AbortController;

  const TOGGLE_BUTTON_COLLAPSED_CLASS = 'toggle-button--collapsed';
  const TOGGLE_BUTTON_WITH_ANIMATION_CLASS = 'toggle-button--with-animation';
  const GROUP_STORAGE_KEY = 'collapsed-search-filter-groups';
  const IS_COLLAPSING_ATTRIBUTE = 'data-is-collapsing';
  const IS_EXPANDING_ATTRIBUTE = 'data-is-expanding';
  const ITEMS_STORAGE_KEY = 'expanded-search-filter-items';

  const getFilterItemsCollapsibeElement = (filtersGroupElement: HTMLElement) => filtersGroupElement
    .querySelector('.js-filters-item-collapsible') as HTMLElement;

  const getFiltersGroupCollapsibeElement = (filtersGroupElement: HTMLElement) => filtersGroupElement
    .querySelector('.js-filters-group-collapsible') as HTMLElement;

  const getFiltersGroupKey = (filtersGroupElement: HTMLElement) => filtersGroupElement.getAttribute('data-key') || '';

  const getToggleGroupButtonElement = (filtersGroupElement: HTMLElement) => filtersGroupElement
    .querySelector('.js-toggle-filters-group-button') as HTMLElement;

  const getToggleItemsButtonElement = (filtersGroupElement: HTMLElement) => filtersGroupElement
    .querySelector('.js-toggle-filter-items-button') as HTMLElement;

  const initialize = () => {
    cleanup();

    abortController = new AbortController();
    (document.querySelectorAll('.js-filters-group') as NodeListOf<HTMLElement>).forEach(initializeFiltersGroup);
  };

  const initializeFiltersGroup = (filtersGroupElement: HTMLElement) => {
    initializeToggleItems(filtersGroupElement);
    initializeToggleGroup(filtersGroupElement);
  };

  const initializeToggleGroup = (filtersGroupElement: HTMLElement) => {
    const toggleGroupButtonElement = getToggleGroupButtonElement(filtersGroupElement) as HTMLElement;
    if (!toggleGroupButtonElement) {
      return;
    }

    if (isFiltersGroupSavedAsCollapsed(filtersGroupElement)) {
      collapseFiltersGroup(filtersGroupElement, false);
    } else {
      expandFiltersGroup(filtersGroupElement, false);
    }

    setTimeout(() => {
      toggleGroupButtonElement.classList.add(TOGGLE_BUTTON_WITH_ANIMATION_CLASS);
    }, 0);

    toggleGroupButtonElement.addEventListener('click', () => {
      if (isToggleButtonMarkedAsCollapsed(toggleGroupButtonElement)) {
        expandFiltersGroup(filtersGroupElement);
        return;
      }
      collapseFiltersGroup(filtersGroupElement);
    }, { signal: abortController.signal });
  };

  const initializeToggleItems = (filtersGroupElement: HTMLElement) => {
    const toggleItemsButtonElement = getToggleItemsButtonElement(filtersGroupElement);
    if (!toggleItemsButtonElement) {
      return;
    }

    if (areFilterItemsSavedAsExpanded(filtersGroupElement)) {
      expandFilterItems(filtersGroupElement, false);
    } else {
      collapseFilterItems(filtersGroupElement, false);
    }

    toggleItemsButtonElement.addEventListener('click', () => {
      if (isToggleButtonMarkedAsCollapsed(toggleItemsButtonElement)) {
        expandFilterItems(filtersGroupElement);
        return;
      }
      collapseFilterItems(filtersGroupElement);
    }, { signal: abortController.signal });
  };

  const collapseFiltersGroup = (filtersGroupElement: HTMLElement, withAnimation = true) => {
    const toggleButtonElement = getToggleGroupButtonElement(filtersGroupElement);

    markToggleButtonAs(toggleButtonElement, 'collapsed');

    const collapsibleElement = getFiltersGroupCollapsibeElement(filtersGroupElement);
    collapseElement(collapsibleElement, withAnimation);

    saveFiltersGroupAsCollapsed(filtersGroupElement);
  };

  const expandFiltersGroup = (filtersGroupElement: HTMLElement, withAnimation = true) => {
    const toggleButtonElement = getToggleGroupButtonElement(filtersGroupElement);

    markToggleButtonAs(toggleButtonElement, 'expanded');

    const collapsibleElement = getFiltersGroupCollapsibeElement(filtersGroupElement);
    expandElement(collapsibleElement, withAnimation);

    saveFiltersGroupAsExpanded(filtersGroupElement);
  };

  const collapseFilterItems = (filtersGroupElement: HTMLElement, withAnimation = true) => {
    const toggleButtonElement = getToggleItemsButtonElement(filtersGroupElement);

    const toState = 'collapsed';
    markToggleButtonAs(toggleButtonElement, toState);
    toggleElementText(toggleButtonElement, toState);

    const collapsibleElement = getFilterItemsCollapsibeElement(filtersGroupElement);
    collapseElement(collapsibleElement, withAnimation);

    saveFilterItemsAsCollapsed(filtersGroupElement);
  };

  const expandFilterItems = (filtersGroupElement: HTMLElement, withAnimation = true) => {
    const toggleButtonElement = getToggleItemsButtonElement(filtersGroupElement);

    const toState = 'expanded';
    markToggleButtonAs(toggleButtonElement, toState);
    toggleElementText(toggleButtonElement, toState);

    const collapsibleElement = getFilterItemsCollapsibeElement(filtersGroupElement);
    expandElement(collapsibleElement, withAnimation);

    saveFilterItemsAsExpanded(filtersGroupElement);
  };

  const markToggleButtonAs = (toggleButtonElement: HTMLElement, markAs: 'collapsed' | 'expanded') => {
    if (markAs === 'collapsed') {
      toggleButtonElement.setAttribute('aria-expanded', 'false');
      toggleButtonElement.classList.add(TOGGLE_BUTTON_COLLAPSED_CLASS);
      return;
    }

    toggleButtonElement.setAttribute('aria-expanded', 'true');
    toggleButtonElement.classList.remove(TOGGLE_BUTTON_COLLAPSED_CLASS);
  };

  const isToggleButtonMarkedAsCollapsed = (toggleButtonElement: HTMLElement) => toggleButtonElement.classList
    .contains(TOGGLE_BUTTON_COLLAPSED_CLASS);

  const collapseElement = (element: HTMLElement, withAnimation: boolean) => {
    if (withAnimation === false) {
      element.setAttribute('hidden', '');
      element.style.height = '0px';
      element.style.overflow = 'hidden';
      return;
    }

    markElementAsCollapsing(element);

    element.style.overflow = 'hidden';
    element.style.height = `${element.scrollHeight}px`;

    setTimeout(() => {
      element.style.height = '0px';
    }, 0);

    element.addEventListener('transitionend', () => {
      markElementAsNotCollapsing(element);

      if (isElementMarkedAsExpanding(element)) {
        // The user clicked fast enough to expand the group again before the animation ended.
        return;
      }

      element.setAttribute('hidden', '');
    }, { once: true });
  };

  const expandElement = (element: HTMLElement, withAnimation: boolean) => {
    if (withAnimation === false) {
      element.removeAttribute('hidden');
      element.style.height = 'auto';
      element.style.overflow = '';
      return;
    }

    markElementAsExpanding(element);

    element.removeAttribute('hidden');
    element.style.height = `${element.scrollHeight}px`;
    element.style.overflow = 'hidden';

    element.addEventListener('transitionend', () => {
      markElementAsNotExpanding(element);

      if (isElementMarkedAsCollapsing(element)) {
        // The user clicked fast enough to collapse the group again before the animation ended.
        return;
      }
      element.style.height = '';
      element.style.overflow = '';
    }, { once: true });
  };

  const markElementAsCollapsing = (element: HTMLElement) => markElementAs(element, IS_COLLAPSING_ATTRIBUTE);
  const markElementAsNotCollapsing = (element: HTMLElement) => markElementAsNot(element, IS_COLLAPSING_ATTRIBUTE);
  const isElementMarkedAsCollapsing = (element: HTMLElement) => element.getAttribute(IS_COLLAPSING_ATTRIBUTE) !== null;
  const markElementAsExpanding = (element: HTMLElement) => markElementAs(element, IS_EXPANDING_ATTRIBUTE);
  const markElementAsNotExpanding = (element: HTMLElement) => markElementAsNot(element, IS_EXPANDING_ATTRIBUTE);
  const isElementMarkedAsExpanding = (element: HTMLElement) => isElementMarkedAs(element, IS_EXPANDING_ATTRIBUTE);

  const markElementAs = (element: HTMLElement, attribute: string) => element.setAttribute(attribute, '');
  const markElementAsNot = (element: HTMLElement, attribute: string) => element.removeAttribute(attribute);
  const isElementMarkedAs = (element: HTMLElement, attribute: string) => element.hasAttribute(attribute);

  const toggleElementText = (element: HTMLElement, state: 'collapsed' | 'expanded') => {
    const readFromAttribute = state === 'collapsed' ? 'data-text-collapsed' : 'data-text-expanded';
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

  const getCollapsedFiltersGroups = () => getSetFromLocalStorage(GROUP_STORAGE_KEY);
  const saveCollapsedFiltersGroups = (set: Set<string>) => saveSetInLocalStorage(GROUP_STORAGE_KEY, set);

  const getExpandedFilterItems = () => getSetFromLocalStorage(ITEMS_STORAGE_KEY);
  const saveExpandedFilterItems = (set: Set<string>) => saveSetInLocalStorage(ITEMS_STORAGE_KEY, set);

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
