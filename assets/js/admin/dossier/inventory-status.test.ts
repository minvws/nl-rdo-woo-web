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

  describe('when the inventory status says an update is needed', () => {
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
  });
});
