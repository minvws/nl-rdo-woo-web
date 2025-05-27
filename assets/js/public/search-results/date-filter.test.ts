import { isElementHidden } from '@js/utils';
import {
  MockInstance,
  afterEach,
  beforeEach,
  describe,
  expect,
  test,
} from 'vitest';
import { dateFilters } from './date-filters';

describe('The "dateFilters" function', () => {
  describe('the "initialize" function', () => {
    const { cleanup, initialize } = dateFilters();

    beforeEach(() => {
      document.body.innerHTML = `
        <input id="date-from" type="date" name="from" value="">
        <input id="date-to" type="date" name="to" value="">
        <div class="hidden" id="filter-dates-error">Mocked date period error message</div>
      `;
    });

    afterEach(() => {
      cleanup();
    });

    const getDateElement = (fromOrTo: 'from' | 'to') =>
      document.getElementById(`date-${fromOrTo}`) as HTMLInputElement;
    const errorMessageIsHidden = () =>
      isElementHidden(document.getElementById('filter-dates-error'));
    const initializeDateFilters = (
      fetchAndUpdateResultsFunction?: MockInstance,
    ) => initialize((fetchAndUpdateResultsFunction as any) || (() => {}));
    const setDate = (fromOrTo: 'from' | 'to', date: string) => {
      const dateElement = getDateElement(fromOrTo);
      dateElement.value = date;
      dateElement.dispatchEvent(new Event('blur'));
    };
    const setDates = (fromDate: string, toDate: string) => {
      setDate('from', fromDate);
      setDate('to', toDate);
    };
    const dateElementIsMarkedAsInvalid = (fromOrTo: 'from' | 'to'): boolean => {
      const dateElement = getDateElement(fromOrTo);
      return [
        dateElement.getAttribute('aria-invalid') === 'true',
        dateElement.getAttribute('aria-describedby') === 'filter-dates-error',
        dateElement.classList.contains('woo-input-text--invalid'),
      ].every(Boolean);
    };
    const dateElementsAreMarkedAsInvalid = (): boolean =>
      dateElementIsMarkedAsInvalid('from') &&
      dateElementIsMarkedAsInvalid('to');

    describe('when an invalid date period is entered (the "to" date is earlier than the "from" date)', () => {
      const setInvalidDatePeriod = () => {
        setDates('2022-01-02', '2022-01-01');
      };
      const setValidDatePeriod = () => {
        setDates('2022-01-01', '2022-01-02');
      };

      test('should display an error message', () => {
        initializeDateFilters();

        expect(errorMessageIsHidden()).toBe(true);

        setInvalidDatePeriod();
        expect(errorMessageIsHidden()).toBe(false);
      });

      test('should hide the error message again when the user adjusts the dates to make it a valid date period', () => {
        initializeDateFilters();

        setDates('2022-01-03', '2022-01-02');
        setInvalidDatePeriod();
        expect(errorMessageIsHidden()).toBe(false);

        setValidDatePeriod();
        expect(errorMessageIsHidden()).toBe(true);
      });

      test('should mark the "from" and "to" date elements as invalid', () => {
        initializeDateFilters();

        expect(dateElementsAreMarkedAsInvalid()).toBe(false);

        setInvalidDatePeriod();
        expect(dateElementsAreMarkedAsInvalid()).toBe(true);
      });

      test('should mark the "from" and "to" date elements as valid again when the user adjusts the dates to make it a valid date period', () => {
        initializeDateFilters();

        setInvalidDatePeriod();
        expect(dateElementsAreMarkedAsInvalid()).toBe(true);

        setValidDatePeriod();
        expect(dateElementsAreMarkedAsInvalid()).toBe(false);
      });
    });

    describe('when entering a "from" date', () => {
      test('should set the earliest possible "to" date when the entered "from" date is a valid one', () => {
        initializeDateFilters();

        expect(getDateElement('to').min).toBeFalsy();

        setDate('from', '2022-01-02');
        expect(getDateElement('to').min).toBe('2022-01-02');
      });

      test('should remove the earliest possible "to" date when the entered "from" date is an invalid one', () => {
        initializeDateFilters();

        setDate('from', '2022-01-03');
        expect(getDateElement('to').min).toBeTruthy();

        setDate('from', '');
        expect(getDateElement('to').min).toBeFalsy();
      });
    });

    describe('when entering a "to" date', () => {
      test('should set the latest possible "from" date when the entered "to" date is a valid one', () => {
        initializeDateFilters();

        expect(getDateElement('from').max).toBeFalsy();

        setDate('to', '2022-01-02');
        expect(getDateElement('from').max).toBe('2022-01-02');
      });

      test('should remove the latest possible "from" date when the entered "to" date is an invalid one', () => {
        initializeDateFilters();

        setDate('to', '2022-01-03');
        expect(getDateElement('from').max).toBeTruthy();

        setDate('to', '');
        expect(getDateElement('from').max).toBeFalsy();
      });
    });
  });
});
