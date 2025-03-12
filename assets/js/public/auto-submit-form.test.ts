import { fireEvent, screen } from '@testing-library/dom';
import {
  afterEach,
  beforeEach,
  describe,
  expect,
  it,
  MockInstance,
  vi,
} from 'vitest';
import { autoSubmitForm } from './auto-submit-form';

describe('The functionality regarding auto submitting forms', () => {
  let cleanup: () => void;
  let initialize: () => void;
  let submitSpy: MockInstance;

  const setup = () => {
    ({ cleanup, initialize } = autoSubmitForm());
    initialize();

    submitSpy = vi.spyOn(getForm(), 'submit').mockImplementation(() => {});
  };

  beforeEach(() => {
    document.body.innerHTML = `
      <form class="js-auto-submit-form" id="form-id" data-testid="form">
        <input type="text" name="test" data-testid="input" />
      </form>
    `;

    setup();
  });

  afterEach(() => {
    cleanup();
    document.body.innerHTML = '';
    window.location.hash = '';
  });

  const getForm = () => screen.getByTestId('form') as HTMLFormElement;
  const getInput = () => screen.getByTestId('input');

  const updateInputValue = () => {
    fireEvent.change(getInput(), { target: { value: 'updated value' } });
  };

  it('should submit the form when an input changes', () => {
    expect(submitSpy).not.toHaveBeenCalled();

    updateInputValue();
    expect(submitSpy).toHaveBeenCalled();
  });

  it('should update the URL hash when an input changes', () => {
    expect(window.location.hash).toBe('');
    updateInputValue();
    expect(window.location.hash).toBe('#form-id');
  });
});
