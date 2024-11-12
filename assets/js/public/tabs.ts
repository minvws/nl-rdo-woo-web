export const tabs = () => {
  let abortController: AbortController;
  let tabButtons: NodeListOf<HTMLButtonElement>;
  let tabList: HTMLElement | null;

  const initialize = () => {
    tabList = document.querySelector('[role="tablist"]');
    if (!tabList) {
      return;
    }

    tabButtons = tabList.querySelectorAll('[role="tab"]');

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
          if (!(currentTarget instanceof HTMLElement)) {
            return;
          }

          window.location.hash = currentTarget.id;
          changeTabs(currentTarget);
        },
        { signal: abortController.signal },
      );
    });
  };

  const enableNavigation = () => {
    // Enable arrow navigation between tabs in the tab list
    let tabFocus = 0;

    if (tabList) {
      tabList.addEventListener(
        'keydown',
        (event: Event) => {
          const { key } = event as KeyboardEvent;

          // Move right
          if (key === 'ArrowRight' || key === 'ArrowLeft') {
            tabButtons[tabFocus].setAttribute('tabindex', '-1');
            if (key === 'ArrowRight') {
              tabFocus += 1;
              // If we're at the end, go to the start
              if (tabFocus >= tabButtons.length) {
                tabFocus = 0;
              }
              // Move left
            } else if (key === 'ArrowLeft') {
              tabFocus -= 1;
              // If we're at the start, move to the end
              if (tabFocus < 0) {
                tabFocus = tabButtons.length - 1;
              }
            }

            tabButtons[tabFocus].setAttribute('tabindex', '0');
            tabButtons[tabFocus].focus();
          }
        },
        { signal: abortController.signal },
      );
    }
  };

  const displayTabByHash = () => {
    const { hash } = window.location;
    if (hash === '') {
      return;
    }

    const tabButtonElement = document.querySelector(
      `[role="tab"][data-tab-target="${hash}"]`,
    ) as HTMLElement;
    if (!tabButtonElement) {
      return;
    }

    changeTabs(tabButtonElement);
  };

  const changeTabs = (tabButtonElement: HTMLElement) => {
    if (!tabButtonElement) {
      return;
    }

    const parent = tabButtonElement.closest('[role="tablist"]');
    if (!parent) {
      return;
    }

    const grandparent = parent.parentNode;
    if (!grandparent) {
      return;
    }

    // Remove all current selected tabs
    parent
      .querySelectorAll('[aria-selected="true"]')
      .forEach((tabElement) =>
        tabElement.setAttribute('aria-selected', 'false'),
      );

    // Set this tab as selected
    tabButtonElement.setAttribute('aria-selected', 'true');

    // Hide all tab panels
    const tabPanelElements = grandparent.querySelectorAll(
      '[role="tabpanel"]',
    ) as NodeListOf<HTMLElement>;
    tabPanelElements.forEach((tabPanelElement: HTMLElement) =>
      tabPanelElement.setAttribute('hidden', 'true'),
    );

    // Show the selected panel
    grandparent.parentNode
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
