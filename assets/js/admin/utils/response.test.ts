import { z } from 'zod';
import { validateResponse } from './response';
import { describe, it, expect } from 'vitest';

describe('The "validateResponse" function', () => {
  const mockedSchema = z.object({
    string: z.string(),
  });

  const expectedResponse = {
    string: 'This is a mocked string',
  };

  const createResponse = (
    isOk = true,
    response: Record<string, unknown> = expectedResponse,
  ) =>
    Promise.resolve({
      ok: isOk,
      statusText: isOk ? 'OK' : 'Not Found',
      json: () => Promise.resolve(response),
    } as Response);

  it('should throw an error if the response is not ok', async () => {
    await expect(
      validateResponse(createResponse(false), mockedSchema),
    ).rejects.toThrow('Not Found');
  });

  it('should return the response when validation succeeds', async () => {
    const result = await validateResponse(createResponse(), mockedSchema);

    expect(result).toEqual(expectedResponse);
  });

  it('should throw an error when validation fails', async () => {
    await expect(
      validateResponse(createResponse(true, { string: 1 }), mockedSchema),
    ).rejects.toThrow('The data returned does not match the expected schema');
  });
});
