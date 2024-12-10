import { getLocation } from '@js/utils';

export const tabs = () => {
  let abortController: AbortController;
  let tabButtons: HTMLButtonElement[];
  let tabList: HTMLElement | null;

  const initialize = () => {
    tabList = document.querySelector('[role="tablist"]');
    if (!tabList) {
      return;
    }

    tabButtons = [
      ...tabList.querySelectorAll<HTMLButtonElement>('[role="tab"]'),
    ];

    abortController = new AbortController();

    displayTabByHash();
    addClickHandler();
    enableNavigation();
  };

  const addClickHandler = () => {
    // Add a click event handler to each tab
    tabButtons.forEach((tabButton) => {
      tabButton.addEventListener(
        'click',
        (event) => {
          const { currentTarget } = event;
          if (!(currentTarget instanceof HTMLButtonElement)) {
            return;
          }

          getLocation().hash = currentTarget.id;
          changeTabs(currentTarget);
        },
        { signal: abortController.signal },
      );
    });
  };

  const enableNavigation = () => {
    if (tabList) {
      tabList.addEventListener(
        'keydown',
        (event: Event) => {
          const { key } = event as KeyboardEvent;

          const isArrowRight = key === 'ArrowRight';
          const isArrowLeft = key === 'ArrowLeft';

          if (!isArrowRight && !isArrowLeft) {
            return;
          }

          let tabIndex = tabButtons.findIndex(
            (tabButton) => tabButton === document.activeElement,
          );

          if (isArrowRight) {
            tabIndex += 1;
            // If we're at the end, go to the start
            if (tabIndex >= tabButtons.length) {
              tabIndex = 0;
            }
            // Move left
          } else if (key === 'ArrowLeft') {
            tabIndex -= 1;
            // If we're at the start, move to the end
            if (tabIndex < 0) {
              tabIndex = tabButtons.length - 1;
            }
          }

          tabButtons[tabIndex].focus();
        },
        { signal: abortController.signal },
      );
    }
  };

  const displayTabByHash = () => {
    const { hash } = getLocation();
    if (hash === '') {
      return;
    }

    const tabButtonElement = tabButtons.find(
      (tabButton) => tabButton.getAttribute('data-tab-target') === hash,
    );

    if (!tabButtonElement) {
      return;
    }

    changeTabs(tabButtonElement);
  };

  const changeTabs = (tabButtonElement: HTMLButtonElement) => {
    const grandparent = tabList?.parentNode;
    if (!grandparent) {
      return;
    }

    // Remove all current selected tabs
    tabButtons.forEach((tabElement) =>
      tabElement.setAttribute('aria-selected', 'false'),
    );

    // Set this tab as selected
    tabButtonElement.setAttribute('aria-selected', 'true');

    // Hide all tab panels
    grandparent
      .querySelectorAll('[role="tabpanel"]')
      .forEach((tabPanelElement) =>
        tabPanelElement.setAttribute('hidden', 'true'),
      );

    // Show the selected panel
    grandparent
      ?.querySelector(`#${tabButtonElement.getAttribute('aria-controls')}`)
      ?.removeAttribute('hidden');
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
