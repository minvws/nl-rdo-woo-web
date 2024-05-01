export const getSearchParamsAndAppendOrDelete = (hasValue: boolean, key: string, value: string) => {
  if (hasValue) {
    return appendToParams(getSearchParams(), key, value);
  }

  return deleteFromParams(getSearchParams(), key, value);
};

export const getSearchParamsAndDelete = (key: string) => {
  const params = getSearchParams();
  params.delete(key);

  return params;
};

export const getSearchParamsAndSet = (key: string, value: string) => {
  const params = getSearchParams();
  params.set(key, value);

  return params;
};

export const getSearchParams = () => {
  const params = new URLSearchParams(window.location.search);
  return rewriteParamKeys(params);
};

export const resetPageNumber = (params: URLSearchParams) => {
  params.delete('page');
  return params;
};

/*
 * Urls constructed in PHP (using `url_encode` in Twig) sometimes result in a query string like:
 * ?dep[0]=Ministerie&dep[1]=AnderMinisterie
 * The keys `dep[0]` and `dep[1]` are rewritten to `dep[]` in this function to make the url functions work as expected.
 */
const rewriteParamKeys = (params: URLSearchParams) => {
  const removeKeys: string[] = [];

  Array.from(params.keys()).forEach((key) => {
    const rewrittenKey = rewiteKey(key);
    if (key === rewrittenKey) {
      return;
    }

    removeKeys.push(key);
    if (params.has(rewrittenKey)) {
      params.append(rewrittenKey, params.get(key) as string);
    } else {
      params.set(rewrittenKey, params.get(key) as string);
    }
  });

  removeKeys.forEach((key) => {
    params.delete(key);
  });

  return params;
};

const rewiteKey = (key: string) => {
  const regex = /(.+)\[\d+\]$/;
  const [, matchBeforeBrackets] = key.match(regex) || [];

  if (matchBeforeBrackets) {
    // This is a key like `dep[0]` or `dep[1]`, rewrite to `dep[]`
    return `${matchBeforeBrackets}[]`;
  }

  return key;
};

const deleteFromParams = (params: URLSearchParams, key: string, value: string) => {
  const paramValues = params.getAll(key);

  if (!paramValues.length) {
    return params;
  }

  params.delete(key);
  paramValues.forEach((paramValue) => {
    if (paramValue !== value) {
      params.append(key, paramValue);
    }
  });

  return params;
};

const appendToParams = (params: URLSearchParams, key: string, value: string) => {
  params.append(key, value);
  return params;
};
