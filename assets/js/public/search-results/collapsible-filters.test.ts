import { fireEvent, screen } from '@testing-library/dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { collapsibleFilters } from './collapsible-filters';

describe('The functionality regarding the collapsible filters on the search page', () => {
  let initialize: () => void;
  let cleanup: () => void;

  const initializeFunctionality = () => {
    if (cleanup) {
      cleanup();
    }

    ({ cleanup, initialize } = collapsibleFilters());
    initialize();
  };

  beforeEach(() => {
    document.body.innerHTML = `
      <fieldset class="js-filters-group" data-key="first-group" data-testid="filters-group">
        <button class="js-toggle-filters-group-button" data-testid="toggle-filters-group-button">
          Toggle filters group
          <span class="js-toggle-filters-group-button-icon" data-testid="toggle-filters-group-button-icon"></span>
        </button>

        <div class="js-filters-group-collapsible" data-testid="filters-group-collapsible">
          <input class="js-search-filter-checkbox" data-testid="initial-item-checkbox" type="checkbox" />

          <div class="js-filters-item-collapsible" data-testid="filters-item-collapsible">
            <input class="js-search-filter-checkbox" data-testid="more-items-checkbox" type="checkbox" />
          </div>

          <button
            class="js-toggle-filter-items-button"
            data-testid="toggle-filter-items-button"
            data-text-collapsed="Show more"
            data-text-expanded="Show less"
          >Show more</button>
        </div>
      </fieldset>
    `;

    vi.spyOn(window, 'matchMedia').mockImplementation(
      () =>
        ({
          matches: true,
        }) as MediaQueryList,
    );

    ({ cleanup, initialize } = collapsibleFilters());
    initialize();
  });

  const getFiltersGroupCollapsible = () =>
    screen.getByTestId('filters-group-collapsible');

  const getToggleFiltersGroupButton = () =>
    screen.getByTestId('toggle-filters-group-button');

  const getFiltersItemCollapsible = () =>
    screen.getByTestId('filters-item-collapsible');

  const getToggleFilterItemsButton = () =>
    screen.getByTestId('toggle-filter-items-button');

  const getMoreItemsCheckbox = () => screen.getByTestId('more-items-checkbox');

  afterEach(() => {
    cleanup();

    vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => null);
  });

  describe('a filters group', () => {
    it('should be expanded by default', () => {
      expect(getFiltersGroupCollapsible()).not.toHaveClass('hidden');
    });

    it('should toggle the visibility of the element when the toggle button is clicked', () => {
      fireEvent.click(getToggleFiltersGroupButton());
      expect(getFiltersGroupCollapsible()).toHaveClass('hidden');

      fireEvent.click(getToggleFiltersGroupButton());
      expect(getFiltersGroupCollapsible()).not.toHaveClass('hidden');
    });

    describe('when saved as collapsed', () => {
      beforeEach(() => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(
          (key: string) =>
            key === 'collapsed-search-filter-groups'
              ? JSON.stringify(['first-group'])
              : null,
        );

        initializeFunctionality();
      });

      it('should be collapsed', () => {
        expect(getFiltersGroupCollapsible()).toHaveClass('hidden');
      });

      describe('when having checkboxes which are checked', () => {
        beforeEach(() => {
          fireEvent.click(getMoreItemsCheckbox());
          initializeFunctionality();
        });

        it('should be expanded', () => {
          expect(getFiltersGroupCollapsible()).not.toHaveClass('hidden');
        });
      });
    });
  });

  describe('an expandable element within a filters group', () => {
    it('should be collapsed by default', () => {
      expect(getFiltersItemCollapsible()).toHaveClass('hidden');
    });

    describe('when having checkboxes which are checked', () => {
      beforeEach(() => {
        fireEvent.click(getMoreItemsCheckbox());
        initializeFunctionality();
      });

      it('should be expanded', () => {
        expect(getFiltersItemCollapsible()).not.toHaveClass('hidden');
      });
    });

    describe('the toggle button', () => {
      it('should toggle the visibility of the corresponding expandable element when clicked', () => {
        fireEvent.click(getToggleFilterItemsButton());
        expect(getFiltersItemCollapsible()).not.toHaveClass('hidden');

        fireEvent.click(getToggleFilterItemsButton());
        expect(getFiltersItemCollapsible()).toHaveClass('hidden');
      });

      it('should have a text based on the attributes "data-text-collapsed" and "data-text-expanded" and the visibility of the corresponding expandable element', () => {
        const toggleButton = getToggleFilterItemsButton();

        fireEvent.click(toggleButton);
        expect(toggleButton).toHaveTextContent('Show less');

        fireEvent.click(toggleButton);
        expect(toggleButton).toHaveTextContent('Show more');
      });
    });

    describe('when saved as expanded', () => {
      beforeEach(() => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(
          (key: string) =>
            key === 'expanded-search-filter-items'
              ? JSON.stringify(['first-group'])
              : null,
        );

        initializeFunctionality();
      });

      it('should be collapsed', () => {
        expect(getFiltersItemCollapsible()).not.toHaveClass('hidden');
      });
    });
  });
});
