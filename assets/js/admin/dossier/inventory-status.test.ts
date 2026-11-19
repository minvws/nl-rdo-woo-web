import { isElementHidden } from '@utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { dossierInventoryStatus } from './inventory-status';

describe('The functionality regarding the dossier inventory status', () => {
  let cleanup: () => void;
  let initialize: () => void;

  interface InventoryStatus {
    hasErrors: boolean;
    needsConfirmation: boolean;
    needsUpdate: boolean;
  }

  const createMockedResponse = (inventoryStatus: InventoryStatus) => ({
    ok: true,
    json: () =>
      Promise.resolve({
        content: '<p>mocked-content-from-response</p>',
        inventoryStatus,
      }),
  });

  const waitForNextResponse = async () => {
    vi.advanceTimersByTime(3000 + 1);
    await vi.waitFor(() => expect(window.fetch).toHaveBeenCalled());
  };

  const getInventoryStatusElement = () =>
    document.querySelector('#js-inventory-status');

  const getContinueLaterElement = () =>
    document.querySelector('.js-inventory-status-continue-later');

  beforeEach(() => {
    vi.useFakeTimers();

    document.body.innerHTML = `
      <div class="js-inventory-status-wrapper">
        <div
          id="js-inventory-status"
          data-done-url="https://mocked-done-url.com"
          data-endpoint="https://mocked-endpoint.com"
        ></div>

        <div class="js-inventory-status-continue-later">
          Continue later
        </div>
      </div>
    `;

    window.fetch = vi
      .fn()
      .mockResolvedValueOnce(
        createMockedResponse({
          hasErrors: false,
          needsConfirmation: false,
          needsUpdate: true,
        }),
      )
      .mockResolvedValueOnce(
        createMockedResponse({
          hasErrors: false,
          needsConfirmation: false,
          needsUpdate: false,
        }),
      );

    vi.spyOn(window, 'location', 'get').mockReturnValue({
      assign: vi.fn(),
    } as any);

    ({ cleanup, initialize } = dossierInventoryStatus());
  });

  afterEach(() => {
    vi.clearAllMocks();
    vi.useRealTimers();
    cleanup();
  });

  test('should make a request to the endpoint provided in the data-endpoint attribute', () => {
    initialize();

    expect(window.fetch).toHaveBeenNthCalledWith(
      1,
      'https://mocked-endpoint.com',
      { signal: expect.any(AbortSignal) },
    );
  });

  test('should update the content of the inventory status element', async () => {
    expect(getInventoryStatusElement()?.innerHTML).toBe('');

    initialize();
    await waitForNextResponse();

    expect(getInventoryStatusElement()?.innerHTML).toBe(
      '<p>mocked-content-from-response</p>',
    );
  });

  test('should make another request if the inventory status says an update is needed', async () => {
    initialize();
    await waitForNextResponse();
    await waitForNextResponse();

    expect(window.fetch).toHaveBeenNthCalledWith(
      2,
      'https://mocked-endpoint.com',
      { signal: expect.any(AbortSignal) },
    );
  });

  test('should stop making requests when the inventory status says an update is not needed', async () => {
    initialize();
    await waitForNextResponse();
    await waitForNextResponse();
    await waitForNextResponse();
    await waitForNextResponse();
    await waitForNextResponse();

    expect(window.fetch).toHaveBeenCalledTimes(2);
  });

  test('should redirect to the done URL when the inventory status says an update is not needed', async () => {
    initialize();
    await waitForNextResponse();

    expect(window.location.assign).not.toHaveBeenCalled();

    await waitForNextResponse();
    expect(window.location.assign).toHaveBeenCalledWith(
      'https://mocked-done-url.com',
    );
  });

  test('should not make a request when the inventory status element does not exist', () => {
    document.body.innerHTML = '';

    initialize();

    expect(window.fetch).not.toHaveBeenCalled();
  });

  test('should not redirect when there is no done URL', async () => {
    getInventoryStatusElement()?.removeAttribute('data-done-url');
    window.fetch = vi.fn().mockResolvedValue(
      createMockedResponse({
        hasErrors: false,
        needsConfirmation: false,
        needsUpdate: false,
      }),
    );

    initialize();
    await waitForNextResponse();

    expect(window.location.assign).not.toHaveBeenCalled();
  });

  describe('when the inventory status says there are errors', () => {
    beforeEach(() => {
      window.fetch = vi.fn().mockResolvedValue(
        createMockedResponse({
          hasErrors: true,
          needsConfirmation: false,
          needsUpdate: true,
        }),
      );
    });

    test('should hide the continue later button', async () => {
      initialize();

      expect(isElementHidden(getContinueLaterElement())).toBe(false);

      await waitForNextResponse();

      expect(isElementHidden(getContinueLaterElement())).toBe(true);
    });

    test('should stop making requests', async () => {
      initialize();
      await waitForNextResponse();
      await waitForNextResponse();

      expect(window.fetch).toHaveBeenCalledTimes(1);
    });
  });

  describe('when the inventory status says a confirmation is needed', () => {
    beforeEach(() => {
      window.fetch = vi.fn().mockResolvedValue(
        createMockedResponse({
          hasErrors: false,
          needsConfirmation: true,
          needsUpdate: true,
        }),
      );
    });

    test('should hide the continue later button and stop making requests', async () => {
      initialize();
      await waitForNextResponse();
      await waitForNextResponse();

      expect(isElementHidden(getContinueLaterElement())).toBe(true);
      expect(window.fetch).toHaveBeenCalledTimes(1);
    });
  });

  describe('when a request fails', () => {
    test('should try again after the wait time when the request fails', async () => {
      window.fetch = vi
        .fn()
        .mockRejectedValueOnce(new TypeError('Failed to fetch'))
        .mockResolvedValue(
          createMockedResponse({
            hasErrors: false,
            needsConfirmation: false,
            needsUpdate: false,
          }),
        );

      initialize();
      await vi.advanceTimersByTimeAsync(0);
      expect(window.fetch).toHaveBeenCalledTimes(1);

      await vi.advanceTimersByTimeAsync(3000);

      expect(window.fetch).toHaveBeenCalledTimes(2);
      expect(window.location.assign).toHaveBeenCalledWith(
        'https://mocked-done-url.com',
      );
    });

    test('should stop trying after five consecutive failures', async () => {
      window.fetch = vi
        .fn()
        .mockRejectedValue(new TypeError('Failed to fetch'));

      initialize();
      await vi.advanceTimersByTimeAsync(3000 * 10);

      expect(window.fetch).toHaveBeenCalledTimes(5);
    });

    test('should treat a response that is not ok as a failure', async () => {
      window.fetch = vi.fn().mockResolvedValue({
        ok: false,
        status: 500,
        json: () => Promise.resolve({}),
      });

      initialize();
      await vi.advanceTimersByTimeAsync(3000 * 10);

      expect(window.fetch).toHaveBeenCalledTimes(5);
      expect(getInventoryStatusElement()?.innerHTML).toBe('');
    });

    test('should stop trying when the response is not valid JSON', async () => {
      window.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: () => Promise.reject(new SyntaxError('Unexpected token <')),
      });

      initialize();
      await vi.advanceTimersByTimeAsync(3000 * 10);

      expect(window.fetch).toHaveBeenCalledTimes(5);
    });

    test('should not try again when the request was aborted', async () => {
      window.fetch = vi
        .fn()
        .mockRejectedValue(new DOMException('Aborted', 'AbortError'));

      initialize();
      await vi.advanceTimersByTimeAsync(3000 * 3);

      expect(window.fetch).toHaveBeenCalledTimes(1);
    });
  });
});
