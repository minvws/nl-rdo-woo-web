export const collapsibleFilters = () => {
    let abortController = null;

    const TOGGLE_BUTTON_COLLAPSED_CLASS = 'toggle-button--collapsed';
    const TOGGLE_BUTTON_WITH_ANIMATION_CLASS = 'toggle-button--with-animation';
    const GROUP_STORAGE_KEY = 'collapsed-search-filter-groups';
    const IS_COLLAPSING_ATTRIBUTE = 'data-is-collapsing';
    const IS_EXPANDING_ATTRIBUTE = 'data-is-expanding';
    const ITEMS_STORAGE_KEY = 'expanded-search-filter-items';

    const getFilterItemsCollapsibeElement = (filtersGroupElement) => filtersGroupElement.querySelector('.js-filters-item-collapsible');
    const getFiltersGroupCollapsibeElement = (filtersGroupElement) => filtersGroupElement.querySelector('.js-filters-group-collapsible');
    const getFiltersGroupKey = (filtersGroupElement) => filtersGroupElement.getAttribute('data-key');
    const getToggleGroupButtonElement = (filtersGroupElement) => filtersGroupElement.querySelector('.js-toggle-filters-group-button');
    const getToggleItemsButtonElement = (filtersGroupElement) => filtersGroupElement.querySelector('.js-toggle-filter-items-button');

    const initialize = () => {
        if (abortController) {
            abortController.abort(); // This will remove event listeners and prevent memory leaks
        }

        abortController = new AbortController();
        document.querySelectorAll('.js-filters-group').forEach(initializeFiltersGroup);
    };

    const initializeFiltersGroup = (filtersGroupElement) => {
        initializeToggleItems(filtersGroupElement);
        initializeToggleGroup(filtersGroupElement);
    };

    const initializeToggleGroup = (filtersGroupElement) => {
        const toggleGroupButtonElement = getToggleGroupButtonElement(filtersGroupElement);
        if (!toggleGroupButtonElement) {
            return;
        }

        isFiltersGroupSavedAsCollapsed(filtersGroupElement) ? collapseFiltersGroup(filtersGroupElement, false) : expandFiltersGroup(filtersGroupElement, false);
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

    const initializeToggleItems = (filtersGroupElement) => {
        const toggleItemsButtonElement = getToggleItemsButtonElement(filtersGroupElement);
        if (!toggleItemsButtonElement) {
            return;
        }

        areFilterItemsSavedAsExpanded(filtersGroupElement) ? expandFilterItems(filtersGroupElement, false) : collapseFilterItems(filtersGroupElement, false);

        toggleItemsButtonElement.addEventListener('click', () => {
            if (isToggleButtonMarkedAsCollapsed(toggleItemsButtonElement)) {
                expandFilterItems(filtersGroupElement);
                return;
            }
            collapseFilterItems(filtersGroupElement);
        }, { signal: abortController.signal });
    };

    const collapseFiltersGroup = (filtersGroupElement, withAnimation) => {
        const toggleButtonElement = getToggleGroupButtonElement(filtersGroupElement);

        markToggleButtonAs(toggleButtonElement, 'collapsed');

        const collapsibleElement = getFiltersGroupCollapsibeElement(filtersGroupElement);
        collapseElement(collapsibleElement, withAnimation);

        saveFiltersGroupAsCollapsed(filtersGroupElement);
    };

    const expandFiltersGroup = (filtersGroupElement, withAnimation) => {
        const toggleButtonElement = getToggleGroupButtonElement(filtersGroupElement);

        markToggleButtonAs(toggleButtonElement, 'expanded');

        const collapsibleElement = getFiltersGroupCollapsibeElement(filtersGroupElement);
        expandElement(collapsibleElement, withAnimation);

        saveFiltersGroupAsExpanded(filtersGroupElement);
    };

    const collapseFilterItems = (filtersGroupElement, withAnimation) => {
        const toggleButtonElement = getToggleItemsButtonElement(filtersGroupElement);

        const toState = 'collapsed';
        markToggleButtonAs(toggleButtonElement, toState);
        toggleElementText(toggleButtonElement, toState);

        const collapsibleElement = getFilterItemsCollapsibeElement(filtersGroupElement);
        collapseElement(collapsibleElement, withAnimation);

        saveFilterItemsAsCollapsed(filtersGroupElement);
    };

    const expandFilterItems = (filtersGroupElement, withAnimation) => {
        const toggleButtonElement = getToggleItemsButtonElement(filtersGroupElement);

        const toState = 'expanded';
        markToggleButtonAs(toggleButtonElement, toState);
        toggleElementText(toggleButtonElement, toState);

        const collapsibleElement = getFilterItemsCollapsibeElement(filtersGroupElement);
        expandElement(collapsibleElement, withAnimation);

        saveFilterItemsAsExpanded(filtersGroupElement);
    };

    const markToggleButtonAs = (toggleButtonElement, markAs) => {
        if (markAs === 'collapsed') {
            toggleButtonElement.setAttribute('aria-expanded', false);
            toggleButtonElement.classList.add(TOGGLE_BUTTON_COLLAPSED_CLASS);
            return;
        }

        toggleButtonElement.setAttribute('aria-expanded', true);
        toggleButtonElement.classList.remove(TOGGLE_BUTTON_COLLAPSED_CLASS);
    };

    const isToggleButtonMarkedAsCollapsed = (toggleButtonElement) => toggleButtonElement.classList.contains(TOGGLE_BUTTON_COLLAPSED_CLASS);

    const collapseElement = (element, withAnimation) => {
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

    const expandElement = (element, withAnimation) => {
        if (withAnimation === false) {
            element.removeAttribute('hidden', '');
            element.style.height = 'auto';
            element.style.overflow = null;
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
            element.style.height = null;
            element.style.overflow = null;
        }, { once: true });
    };

    const markElementAsCollapsing = (element) => markElementAs(element, IS_COLLAPSING_ATTRIBUTE);
    const markElementAsNotCollapsing = (element) => markElementAsNot(element, IS_COLLAPSING_ATTRIBUTE);
    const isElementMarkedAsCollapsing = (element) => element.getAttribute(IS_COLLAPSING_ATTRIBUTE) !== null;
    const markElementAsExpanding = (element) => markElementAs(element, IS_EXPANDING_ATTRIBUTE);
    const markElementAsNotExpanding = (element) => markElementAsNot(element, IS_EXPANDING_ATTRIBUTE);
    const isElementMarkedAsExpanding = (element) => isElementMarkedAs(element, IS_EXPANDING_ATTRIBUTE);

    const markElementAs = (element, attribute) => element.setAttribute(attribute, '');
    const markElementAsNot = (element, attribute) => element.removeAttribute(attribute);
    const isElementMarkedAs = (element, attribute) => element.hasAttribute(attribute);

    const toggleElementText = (element, state) => {
        const readFromAttribute = state === 'collapsed' ? 'data-text-collapsed' : 'data-text-expanded';
        element.textContent = element.getAttribute(readFromAttribute);
    };

    const saveFiltersGroupAsCollapsed = (filtersGroupElement) => {
        const set = getCollapsedFiltersGroups();
        set.add(getFiltersGroupKey(filtersGroupElement));
        saveCollapsedFiltersGroups(set);
    };

    const saveFiltersGroupAsExpanded = (filtersGroupElement) => {
        const set = getCollapsedFiltersGroups();
        set.delete(getFiltersGroupKey(filtersGroupElement));
        saveCollapsedFiltersGroups(set);
    };

    const saveFilterItemsAsCollapsed = (filtersGroupElement) => {
        const set = getExpandedFilterItems();
        set.delete(getFiltersGroupKey(filtersGroupElement));
        saveExpandedFilterItems(set);
    };

    const saveFilterItemsAsExpanded = (filtersGroupElement) => {
        const set = getExpandedFilterItems();
        set.add(getFiltersGroupKey(filtersGroupElement));
        saveExpandedFilterItems(set);
    };

    const areFilterItemsSavedAsExpanded = (filtersGroupElement) => {
        const set = getExpandedFilterItems();
        return set.has(getFiltersGroupKey(filtersGroupElement));
    };

    const isFiltersGroupSavedAsCollapsed = (filtersGroupElement) => {
        const set = getCollapsedFiltersGroups();
        return set.has(getFiltersGroupKey(filtersGroupElement));
    };

    const getCollapsedFiltersGroups = () => getSetFromLocalStorage(GROUP_STORAGE_KEY);
    const saveCollapsedFiltersGroups = (set) => saveSetInLocalStorage(GROUP_STORAGE_KEY, set);

    const getExpandedFilterItems = () => getSetFromLocalStorage(ITEMS_STORAGE_KEY);
    const saveExpandedFilterItems = (set) => saveSetInLocalStorage(ITEMS_STORAGE_KEY, set);

    const getSetFromLocalStorage = (storageKey) => {
        const savedFilterGroups = localStorage.getItem(storageKey);

        if (!savedFilterGroups) {
            return new Set();
        }

        return new Set(JSON.parse(savedFilterGroups));
    };

    const saveSetInLocalStorage = (storageKey, set) => {
        localStorage.setItem(storageKey, JSON.stringify([...set.values()]));
    };

    return {
        initialize,
    }
};
