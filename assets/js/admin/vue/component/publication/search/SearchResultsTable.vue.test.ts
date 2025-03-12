import { mount, VueWrapper } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import SearchResultsTable from './SearchResultsTable.vue';

describe('The "SearchResultsTable" component', () => {
  const mockedResults = [
    { link: 'mocked_link_1', id: 'mocked_id_1', title: 'mocked_title_1' },
    { link: 'mocked_link_2', id: 'mocked_id_2', title: 'mocked_title_2' },
  ];

  interface Options {
    results: typeof mockedResults;
    hideResultId: boolean;
  }

  const createComponent = (options: Partial<Options> = {}) => {
    const { results = mockedResults, hideResultId = false } = options;
    return mount(SearchResultsTable, {
      props: {
        columnResultId: 'Mocked column result id',
        results,
        title: 'Mocked title',
        hideResultId,
      },
      global: {
        directives: {
          'clickable-row': {},
        },
      },
      shallow: true,
    });
  };

  const getNumberOfColumns = (component: VueWrapper) =>
    component.findAll('thead th').length;
  const getRows = (component: VueWrapper) => component.findAll('tbody tr');
  const getTitle = (component: VueWrapper) => component.find('h2');
  const getTable = (component: VueWrapper) => component.find('table');

  beforeEach(() => {
    vi.spyOn(window, 'location', 'get').mockReturnValue({
      assign: vi.fn(),
    } as any);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

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
    const rows = getRows(createComponent());

    expect(rows.length).toBe(mockedResults.length);
    expect(rows[0].text()).toContain('mocked_title_1');
    expect(rows[0].text()).toContain('mocked_id_1');

    expect(rows[1].text()).toContain('mocked_title_2');
  });

  test('should go to the url of the result when clicking on the table row', () => {
    const row = getRows(createComponent())[0];

    expect(window.location.assign).not.toHaveBeenCalled();

    row.trigger('click');
    expect(window.location.assign).toHaveBeenCalledWith('mocked_link_1');
  });

  test('should hide result id column when hideResultId is true', async () => {
    const component = createComponent();

    expect(getNumberOfColumns(component)).toBe(2);

    await (component as any).setProps({ hideResultId: true });
    expect(getNumberOfColumns(component)).toBe(1);
  });

  test('should display nothing when no results are provided', () => {
    const component = createComponent({
      results: [],
    });

    // Remove comments from the HTML
    expect(component.html().replace(/(<!--.*?-->)/g, '')).toBe('');
  });
});
