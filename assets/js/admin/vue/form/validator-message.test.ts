import { describe, expect, test } from 'vitest';
import {
  dateMaxUntilToday,
  email,
  forbidden,
  minLength,
  required,
} from './validator-message';

describe('The validator messages', () => {
  describe('the "dateMaxUntilToday" message', () => {
    test('should return a message saying the value is a date that is in the future', () => {
      expect(dateMaxUntilToday()).toEqual(
        'Deze datum mag niet in de toekomst liggen',
      );
    });
  });

  describe('the "email" message', () => {
    test('should return a message saying the value is not an email', () => {
      expect(email()).toEqual(
        'Vul een geldig e-mailadres in (zoals voor@beeld.com)',
      );
    });
  });

  describe('the "forbidden" message', () => {
    test('should return a message saying the value is forbidden', () => {
      expect(forbidden()).toEqual('Deze waarde is niet toegestaan');
    });
  });

  describe('the "minLength" message', () => {
    test('should return a message saying the value is too short', () => {
      expect(
        minLength({
          actualLength: 1,
          id: 'minLength',
          minLength: 2,
          tooLittleLength: 1,
        }),
      ).toEqual(
        'Vul je invoer aan met 1 karakter. Het minimum aantal karakters is 2.',
      );
    });
  });

  describe('the "required" message', () => {
    test('should return a message saying the value is required', () => {
      expect(required()).toEqual('Deze waarde mag niet leeg zijn.');
    });
  });
});
