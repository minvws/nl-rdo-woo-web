import { fireEvent, screen } from '@testing-library/dom';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { sortableTable } from './sortable-table';

describe('The functionality regarding sortable tables', () => {
  let cleanup: () => void;

  beforeEach(() => {
    document.body.innerHTML = `
      <table
        class="js-sortable-table"
        data-message-sort-asc="Oplopend sorteren"
        data-message-sort-desc="Aflopend sorteren"
      >
        <thead>
          <tr>
            <th class="js-sortable-column" scope="col">Fruit</th>
            <th class="js-sortable-column" scope="col">Aantal</th>
            <th scope="col">Niet sorteerbaar</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>Peer</td><td>10</td><td>Ja</td></tr>
          <tr><td>Appel</td><td>9</td><td>Nee</td></tr>
          <tr><td>Citroen</td><td>100</td><td>Ja</td></tr>
        </tbody>
      </table>
      <table>
        <thead>
          <tr><th class="js-sortable-column" scope="col">Buiten tabel</th></tr>
        </thead>
      </table>
    `;

    const functionality = sortableTable();
    cleanup = functionality.cleanup;
    functionality.initialize();
  });

  afterEach(() => {
    cleanup();
    document.body.innerHTML = '';
  });

  const startsWith = (text: string) => new RegExp(`${text}$`);
  const getButton = (name: string) =>
    screen.getByRole('button', { name: startsWith(name) });
  const getColumnHeader = (name: string) =>
    screen.getByRole('columnheader', { name: startsWith(name) });
  const clickButton = (name: string) => fireEvent.click(getButton(name));
  const getColumnValues = (columnIndex: number) =>
    Array.from(document.querySelectorAll('tbody tr')).map(
      (row) => row.children[columnIndex].textContent,
    );

  it('should put the text of each sortable column in a button', () => {
    expect(getButton('Fruit')).toBeInTheDocument();
    expect(getButton('Aantal')).toBeInTheDocument();
  });

  it('should keep the button inside the column header', () => {
    const button = getButton('Fruit');

    expect(button.parentElement).toBe(getColumnHeader('Fruit'));
  });

  const getIconHref = (name: string) =>
    getButton(name).querySelector('use')?.getAttribute('xlink:href');
  const getIconHrefFor = (iconName: string) =>
    `/img/public/icons.svg?${__GIT_HASH__}#${iconName}`;

  it('should add the sort icon after the text in the button', () => {
    const button = getButton('Fruit');

    expect(button.lastElementChild?.tagName).toBe('svg');
    expect(getIconHref('Fruit')).toBe(getIconHrefFor('sort'));
  });

  it('should show the sort-up icon when sorted ascending', () => {
    clickButton('Fruit');

    expect(getIconHref('Fruit')).toBe(getIconHrefFor('sort-up'));
  });

  it('should show the sort-down icon when sorted descending', () => {
    clickButton('Fruit');
    clickButton('Fruit');

    expect(getIconHref('Fruit')).toBe(getIconHrefFor('sort-down'));
  });

  it('should show the sort icon again for columns that are no longer sorted', () => {
    clickButton('Fruit');
    clickButton('Aantal');

    expect(getIconHref('Fruit')).toBe(getIconHrefFor('sort'));
  });

  it('should announce that clicking sorts ascending when not sorted', () => {
    expect(getButton('Fruit')).toHaveAccessibleName('Oplopend sorteren: Fruit');
  });

  it('should announce that clicking sorts descending when sorted ascending', () => {
    clickButton('Fruit');

    expect(getButton('Fruit')).toHaveAccessibleName('Aflopend sorteren: Fruit');
  });

  it('should announce that clicking sorts ascending when sorted descending', () => {
    clickButton('Fruit');
    clickButton('Fruit');

    expect(getButton('Fruit')).toHaveAccessibleName('Oplopend sorteren: Fruit');
  });

  it('should announce that clicking sorts ascending again for columns that are no longer sorted', () => {
    clickButton('Fruit');
    clickButton('Aantal');

    expect(getButton('Fruit')).toHaveAccessibleName('Oplopend sorteren: Fruit');
  });

  it('should keep a single icon in the button after sorting', () => {
    clickButton('Fruit');
    clickButton('Fruit');

    expect(getButton('Fruit').querySelectorAll('svg')).toHaveLength(1);
  });

  it('should not add a button to columns that are not sortable', () => {
    expect(
      screen.queryByRole('button', { name: 'Niet sorteerbaar' }),
    ).not.toBeInTheDocument();
  });

  it('should not add a button to sortable columns outside a sortable table', () => {
    expect(
      screen.queryByRole('button', { name: 'Buiten tabel' }),
    ).not.toBeInTheDocument();
  });

  it('should mark each sortable column as not sorted', () => {
    expect(getColumnHeader('Fruit')).toHaveAttribute('aria-sort', 'none');
    expect(getColumnHeader('Aantal')).toHaveAttribute('aria-sort', 'none');
  });

  it('should not mark a column that is not sortable as sorted', () => {
    expect(getColumnHeader('Niet sorteerbaar')).not.toHaveAttribute(
      'aria-sort',
    );
  });

  it('should not change the order of the rows before a button is clicked', () => {
    expect(getColumnValues(0)).toEqual(['Peer', 'Appel', 'Citroen']);
  });

  it('should sort ascending on the first click', () => {
    clickButton('Fruit');

    expect(getColumnHeader('Fruit')).toHaveAttribute('aria-sort', 'ascending');
    expect(getColumnValues(0)).toEqual(['Appel', 'Citroen', 'Peer']);
  });

  it('should sort descending on the second click', () => {
    clickButton('Fruit');
    clickButton('Fruit');

    expect(getColumnHeader('Fruit')).toHaveAttribute('aria-sort', 'descending');
    expect(getColumnValues(0)).toEqual(['Peer', 'Citroen', 'Appel']);
  });

  it('should sort ascending again on the third click', () => {
    clickButton('Fruit');
    clickButton('Fruit');
    clickButton('Fruit');

    expect(getColumnHeader('Fruit')).toHaveAttribute('aria-sort', 'ascending');
    expect(getColumnValues(0)).toEqual(['Appel', 'Citroen', 'Peer']);
  });

  it('should sort the other cells of a row along with the clicked column', () => {
    clickButton('Fruit');

    expect(getColumnValues(2)).toEqual(['Nee', 'Ja', 'Ja']);
  });

  it('should sort numbers by their value instead of alphabetically', () => {
    clickButton('Aantal');

    expect(getColumnValues(1)).toEqual(['9', '10', '100']);
  });

  it('should mark the other sortable columns as not sorted', () => {
    clickButton('Fruit');
    clickButton('Aantal');

    expect(getColumnHeader('Fruit')).toHaveAttribute('aria-sort', 'none');
    expect(getColumnHeader('Aantal')).toHaveAttribute('aria-sort', 'ascending');
  });

  it('should sort ascending when switching to another column that was sorted descending before', () => {
    clickButton('Fruit');
    clickButton('Fruit');
    clickButton('Aantal');
    clickButton('Fruit');

    expect(getColumnHeader('Fruit')).toHaveAttribute('aria-sort', 'ascending');
  });

  it('should not sort anymore after cleaning up', () => {
    cleanup();
    clickButton('Fruit');

    expect(getColumnHeader('Fruit')).toHaveAttribute('aria-sort', 'none');
    expect(getColumnValues(0)).toEqual(['Peer', 'Appel', 'Citroen']);
  });

  describe('when a table has fewer than two rows', () => {
    const initializeWithRows = (rows: string) => {
      cleanup();
      document.body.innerHTML = `
        <table class="js-sortable-table">
          <thead>
            <tr><th class="js-sortable-column" scope="col">Fruit</th></tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      `;

      const functionality = sortableTable();
      cleanup = functionality.cleanup;
      functionality.initialize();
    };

    it('should not make the columns sortable when the table has no rows', () => {
      initializeWithRows('');

      expect(screen.queryByRole('button')).not.toBeInTheDocument();
      expect(getColumnHeader('Fruit')).not.toHaveAttribute('aria-sort');
    });

    it('should not make the columns sortable when the table has one row', () => {
      initializeWithRows('<tr><td>Peer</td></tr>');

      expect(screen.queryByRole('button')).not.toBeInTheDocument();
      expect(getColumnHeader('Fruit')).not.toHaveAttribute('aria-sort');
    });

    it('should make the columns sortable when the table has two rows', () => {
      initializeWithRows('<tr><td>Peer</td></tr><tr><td>Appel</td></tr>');

      expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('should count rows that are hidden', () => {
      initializeWithRows(
        '<tr><td>Peer</td></tr><tr hidden><td>Appel</td></tr>',
      );

      expect(screen.getByRole('button')).toBeInTheDocument();
    });
  });
});
