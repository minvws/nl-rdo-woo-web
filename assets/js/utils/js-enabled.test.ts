import { describe, expect, test } from '@jest/globals';
import { jsEnabled } from './js-enabled';

describe('The "jsEnabled" function', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <!doctype html>
      <html class="no-js">
        <head>
          <meta charset="utf-8">
          <title>Test</title>
        </head>
        <body></body>
      </html>
    `;
  });

  test('removes the "no-js" class name from the html element', () => {
    jsEnabled();
    expect(document.documentElement.classList.contains('no-js')).toBe(false);
  });

  test('adds the "js" class name to the html element', () => {
    jsEnabled();
    expect(document.documentElement.classList.contains('js')).toBe(true);
  });
});
