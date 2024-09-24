import SearchResultsTable from '@admin-fe/component/publication/search/SearchResultsTable.vue';
import { mount, VueWrapper } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

describe('The "SearchResultsTable" component', () => {
  const mockedResults = [
    { link: 'mocked_link_1', id: 'mocked_id_1', title: 'mocked_title_1' },
    { link: 'mocked_link_2', id: 'mocked_id_2', title: 'mocked_title_2' },
  ];

  const createComponent = (results = mockedResults) => mount(SearchResultsTable, {
    props: {
      columnResultId: 'Mocked column result id',
      results,
      title: 'Mocked title',
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
    expect(title.element.id).toBe(table.element.getAttribute('aria-labelledby'));
  });

  test('should display the provided text for the result id', () => {
    const component = createComponent();
    expect(component.text()).toContain('Mocked column result id');
  });

  test('should display all provided results', () => {
    const component = createComponent();

    const rows = component.findAll('tbody tr');

    expect(rows.length).toBe(mockedResults.length);
    expect(rows[0].text()).toContain('mocked_title_1');
    expect(rows[0].text()).toContain('mocked_id_1');

    expect(rows[1].text()).toContain('mocked_title_2');
  });

  test('should display nothing when no results are provided', () => {
    const component = createComponent([]);

    // Remove comments from the HTML
    expect(component.html().replace(/(<!--.*?-->)/g, '')).toBe('');
  });
});
