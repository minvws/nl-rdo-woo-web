export const validateResponse = async (promise: Promise<Response>) => {
  const response = await promise;

  if (!response.json) {
    return;
  }

  return response.json();
};
