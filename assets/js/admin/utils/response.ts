import { z } from 'zod';

export const validateResponse = async <T extends z.ZodSchema>(
  promise: Promise<Response>,
  schema: T,
): Promise<z.infer<T>> => {
  const response = await promise;
  if (!response.ok) {
    throw new Error(response.statusText);
  }

  const json = await response.json();
  return validateData(json, schema);
};

export const validateData = <T extends z.ZodSchema>(
  data: unknown,
  schema: T,
): z.infer<T> => {
  try {
    return schema.parse(data);
  } catch (error) {
    throw new Error('The data returned does not match the expected schema', {
      cause: error,
    });
  }
};
