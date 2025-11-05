import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { confirmAction } from './confirm-action';

describe('The "confirmAction" function', () => {
  const getFormElement = () => document.querySelector('form');
  const getSelectElement = () => document.querySelector('select');
  const submitForm = () => {
    getFormElement()?.dispatchEvent(new Event('submit'));
  };
  const setSelectValue = (value: string) => {
    const selectElement = getSelectElement();
    if (selectElement) {
      selectElement.value = value;
    }
  };

  let cleanup: () => void;
  let initialize: () => void;

  beforeEach(() => {
    document.body.innerHTML = `
      <div class="js-confirm-action">
        <form>
          <select>
            <option value="action1">Action 1</option>
            <option value="action2">Action 2</option>
          </select>
        </form>
        <div class="js-action" data-key="action1" data-confirmation="Are you sure you want to perform action 1?" />
      </div>

      <div class="js-confirm-action"></div>

      <div class="js-confirm-action">
        <form></form>
      </div>

      <div class="js-confirm-action">
        <form></form>

        <div class="js-action" data-key="action1" data-confirmation="Are you sure you want to perform action 1?" />
      </div>
    `;

    window.confirm = vi.fn();

    ({ cleanup, initialize } = confirmAction());
    initialize();
  });

  afterEach(() => {
    cleanup();
  });

  describe('when the form is submitted', () => {
    test('should ask for confirmation if the user submits the form with a selected action which has a confirmation', () => {
      setSelectValue('action1');
      submitForm();
      expect(window.confirm).toHaveBeenCalledWith(
        'Are you sure you want to perform action 1?',
      );
    });

    test('should not ask for confirmation if the user submits the form with a selected action which has no confirmation', () => {
      setSelectValue('action2');
      submitForm();
      expect(window.confirm).not.toHaveBeenCalled();
    });
  });
});
