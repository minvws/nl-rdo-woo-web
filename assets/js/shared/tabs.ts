import { getLocation } from '@js/utils';

export const tabs = () => {
  const abortController = new AbortController();

  const initialize = () => {
    [...document.querySelectorAll<HTMLDivElement>('.js-tabs')].forEach(
      initializeTabsElement,
    );
  };

  const initializeTabsElement = (tabsElement: HTMLDivElement) => {
    const tabListElement =
      tabsElement.querySelector<HTMLUListElement>('[role="tablist"]');

    if (!tabListElement) {
      return;
    }

    const tabButtonElements = [
      ...tabListElement.querySelectorAll<HTMLButtonElement>('[role="tab"]'),
    ];
    const tabContentElements = [
      ...tabsElement.querySelectorAll<HTMLDivElement>('[role="tabpanel"]'),
    ];

    addTabButtonsClickHandler(tabButtonElements, tabContentElements);
    enableNavigationForList(
      tabListElement,
      tabButtonElements,
      tabContentElements,
    );
    displayTabByHash(tabButtonElements, tabContentElements);
  };

  const addTabButtonsClickHandler = (
    tabButtonElements: HTMLButtonElement[],
    tabContentElements: HTMLDivElement[],
  ) => {
    tabButtonElements.forEach((tabButtonElement) => {
      tabButtonElement.addEventListener(
        'click',
        () => {
          activateTabButton(
            tabButtonElement,
            tabButtonElements,
            tabContentElements,
          );
        },
        { signal: abortController.signal },
      );
    });
  };

  const activateTabButton = (
    tabButtonElement: HTMLButtonElement,
    tabButtonElements: HTMLButtonElement[],
    tabContentElements: HTMLDivElement[],
  ) => {
    getLocation().hash = tabButtonElement.id;
    changeTabs(tabButtonElement, tabButtonElements, tabContentElements);
  };

  const enableNavigationForList = (
    tabListElement: HTMLUListElement,
    tabButtonElements: HTMLButtonElement[],
    tabContentElements: HTMLDivElement[],
  ) => {
    tabListElement.addEventListener(
      'keydown',
      (event: Event) => {
        const { key } = event as KeyboardEvent;

        const isArrowRight = key === 'ArrowRight';
        const isArrowLeft = key === 'ArrowLeft';

        if (!isArrowRight && !isArrowLeft) {
          return;
        }

        let tabIndex = tabButtonElements.findIndex(
          (tabButtonElement) => tabButtonElement === document.activeElement,
        );

        if (isArrowRight) {
          tabIndex += 1;
          // If we're at the end, go to the start
          if (tabIndex >= tabButtonElements.length) {
            tabIndex = 0;
          }
          // Move left
        } else if (key === 'ArrowLeft') {
          tabIndex -= 1;
          // If we're at the start, move to the end
          if (tabIndex < 0) {
            tabIndex = tabButtonElements.length - 1;
          }
        }

        const tabButtonElement = tabButtonElements[tabIndex];
        tabButtonElement.focus();
        activateTabButton(
          tabButtonElement,
          tabButtonElements,
          tabContentElements,
        );
      },
      { signal: abortController.signal },
    );
  };

  const displayTabByHash = (
    tabButtonElements: HTMLButtonElement[],
    tabContentElements: HTMLDivElement[],
  ) => {
    const { hash } = getLocation();
    if (hash === '') {
      return;
    }

    const tabButtonElement = tabButtonElements.find(
      (currentTabButtonElement) =>
        currentTabButtonElement.getAttribute('data-tab-target') === hash,
    );

    if (!tabButtonElement) {
      return;
    }

    changeTabs(tabButtonElement, tabButtonElements, tabContentElements);
  };

  const changeTabs = (
    tabButtonElement: HTMLButtonElement,
    tabButtonElements: HTMLButtonElement[],
    tabContentElements: HTMLDivElement[],
  ) => {
    // Remove all current selected tabs
    tabButtonElements.forEach((currentTabButtonElement) => {
      currentTabButtonElement.setAttribute('aria-selected', 'false');
      currentTabButtonElement.setAttribute('tabindex', '-1');
    });

    // Set this tab as selected
    tabButtonElement.setAttribute('aria-selected', 'true');
    tabButtonElement.removeAttribute('tabindex');

    // Hide all tab panels
    tabContentElements.forEach((currentTabContentElement) =>
      currentTabContentElement.setAttribute('hidden', 'true'),
    );

    // Show the selected panel
    tabContentElements
      .find(
        (currentTabContentElement) =>
          tabButtonElement.getAttribute('aria-controls') ===
          currentTabContentElement.id,
      )
      ?.removeAttribute('hidden');
  };

  const cleanup = () => {
    abortController.abort();
  };

  return {
    cleanup,
    initialize,
  };
};
