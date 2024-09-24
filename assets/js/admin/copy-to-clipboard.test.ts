import { isElementVisible } from '@js/utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { copyToClipboard } from './copy-to-clipboard';

describe('the "copyToClipboard" function', () => {
  let cleanup: () => void;
  let initialize: () => void;

  beforeEach(() => {
    document.body.innerHTML = `
      <span class="js-copy-to-clipboard" data-copy-to-clipboard="Hello world">
          <span class="js-copy-icon"></span>
          <span class="js-success-icon hidden"></span>
      </span>
    `;

    ({ cleanup, initialize } = copyToClipboard());
    initialize();

    Object.assign(navigator, {
      clipboard: {
        writeText: vi.fn(() => Promise.resolve()),
      },
    });

    vi.useFakeTimers();
  });

  afterEach(() => {
    cleanup();
    vi.useRealTimers();
    vi.clearAllMocks();
  });

  const getCopyToClipboardElement = () => document.querySelector('.js-copy-to-clipboard') as HTMLElement;
  const getCopyIcon = () => document.querySelector('.js-copy-icon') as HTMLElement;
  const getCopiedIcon = () => document.querySelector('.js-success-icon') as HTMLElement;

  describe('when copying text', () => {
    test('should copy the text to the clipboard when the element is clicked', async () => {
      expect(navigator.clipboard.writeText).not.toHaveBeenCalled();
      getCopyToClipboardElement().click();
      expect(navigator.clipboard.writeText).toHaveBeenCalledWith('Hello world');
    });

    test('should show the "text is copied" icon and hide the "copy text" icon for a while', async () => {
      expect(isElementVisible(getCopyIcon())).toBe(true);
      expect(isElementVisible(getCopiedIcon())).toBe(false);

      getCopyToClipboardElement().click();
      await Promise.resolve();

      expect(isElementVisible(getCopyIcon())).toBe(false);
      expect(isElementVisible(getCopiedIcon())).toBe(true);

      vi.advanceTimersByTime(2001);

      expect(isElementVisible(getCopyIcon())).toBe(true);
      expect(isElementVisible(getCopiedIcon())).toBe(false);
    });
  });
});
