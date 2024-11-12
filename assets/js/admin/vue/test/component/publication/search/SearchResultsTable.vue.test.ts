import SearchResultsTable from '@admin-fe/component/publication/search/SearchResultsTable.vue';
import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "SearchResultsTable" component', () => {
  const mockedResults = [
    { link: 'mocked_link_1', id: 'mocked_id_1', title: 'mocked_title_1' },
    { link: 'mocked_link_2', id: 'mocked_id_2', title: 'mocked_title_2' },
  ];

  const defaultCreateComponentOptions = {
    results: mockedResults,
    hideResultId: false,
  };

  const createComponent = (
    options: {
      results: typeof mockedResults;
      hideResultId: boolean;
    } = defaultCreateComponentOptions,
  ) =>
    mount(SearchResultsTable, {
      props: {
        columnResultId: 'Mocked column result id',
        results: options.results,
        title: 'Mocked title',
        hideResultId: options.hideResultId,
      },
      global: {
        directives: {
          'clickable-row': {},
        },
      },
      shallow: true,
    });

  const getTitle = (component: VueWrapper) => component.find('h2');
  const getTable = (component: VueWrapper) => component.find('table');

  test('should display the provided title', () => {
    const component = createComponent();
    const title = getTitle(component);
    const table = getTable(component);

    expect(title.text()).toContain('Mocked title');
    expect(title.element.id).toBe(
      table.element.getAttribute('aria-labelledby'),
    );
  });

  test('should display the provided text for the result id', () => {
    const component = createComponent();
    expect(component.text()).toContain('Mocked column result id');
  });

  test('should display all provided results', () => {
    const component = createComponent();

    const rows = component.findAll('tbody tr');
    const columns = component.findAll('thead tr th');

    expect(rows.length).toBe(mockedResults.length);
    expect(columns.length).toBe(Object.keys(mockedResults[0]).length);
    expect(rows[0].text()).toContain('mocked_title_1');
    expect(rows[0].text()).toContain('mocked_id_1');

    expect(rows[1].text()).toContain('mocked_title_2');
  });

  test('should hide result id column when hideResultId is true', () => {
    const component = createComponent({
      ...defaultCreateComponentOptions,
      hideResultId: true,
    });

    const columns = component.findAll('thead tr th');

    expect(columns.length).toBe(Object.keys(mockedResults[0]).length - 1);
  });

  test('should display nothing when no results are provided', () => {
    const component = createComponent({
      ...defaultCreateComponentOptions,
      results: [],
    });

    // Remove comments from the HTML
    expect(component.html().replace(/(<!--.*?-->)/g, '')).toBe('');
  });
});
