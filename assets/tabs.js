import { onDomReady } from "@minvws/manon/utils.js";

onDomReady(() => {
    const tabButtons = document.querySelectorAll('[role="tab"]');
    const tabList = document.querySelector('[role="tablist"]');

    const abortController = new AbortController();

    // Add a click event handler to each tab
    tabButtons.forEach((tabButton) => {
        tabButton.addEventListener("click", (event) => {
            window.location.hash = event.currentTarget.id;
            changeTabs(event.currentTarget);
        }, { signal: abortController.signal });
    });

    displayTabByHash();

    // Enable arrow navigation between tabs in the tab list
    let tabFocus = 0;

    if (tabList) {
        tabList.addEventListener("keydown", (e) => {
            // Move right
            if (e.key === "ArrowRight" || e.key === "ArrowLeft") {
                tabs[tabFocus].setAttribute("tabindex", -1);
                if (e.key === "ArrowRight") {
                    tabFocus++;
                    // If we're at the end, go to the start
                    if (tabFocus >= tabs.length) {
                        tabFocus = 0;
                    }
                    // Move left
                } else if (e.key === "ArrowLeft") {
                    tabFocus--;
                    // If we're at the start, move to the end
                    if (tabFocus < 0) {
                        tabFocus = tabs.length - 1;
                    }
                }

                tabs[tabFocus].setAttribute("tabindex", 0);
                tabs[tabFocus].focus();
            }
        }, { signal: abortController.signal });
    }

    function displayTabByHash() {
        const { hash } = window.location;
        if (hash === '') {
            return;
        }

        const tabButtonElement = document.querySelector(`[role="tab"][data-tab-target="${hash}"]`);
        if (!tabButtonElement) {
            return;
        }

        changeTabs(tabButtonElement);
    }

    function changeTabs(tabButtonElement) {
        if (!tabButtonElement) {
            return;
        }

        const parent = tabButtonElement.closest('[role="tablist"]');
        const grandparent = parent.parentNode;

        // Remove all current selected tabs
        parent
            .querySelectorAll('[aria-selected="true"]')
            .forEach((t) => t.setAttribute("aria-selected", false));

        // Set this tab as selected
        tabButtonElement.setAttribute("aria-selected", true);

        // Hide all tab panels
        grandparent
            .querySelectorAll('[role="tabpanel"]')
            .forEach((p) => p.setAttribute("hidden", true));

        // Show the selected panel
        grandparent.parentNode
            .querySelector(`#${tabButtonElement.getAttribute("aria-controls")}`)
            .removeAttribute("hidden");
    }

    window.addEventListener('beforeunload', () => abortController.abort(), { once: true });
});
