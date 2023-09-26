export const getSearchParamsAndAppendOrDelete = (hasValue, key, value) => {
    if (hasValue) {
        return appendToParams(getSearchParams(), key, value);
    }

    return deleteFromParams(getSearchParams(), key, value);
};

export const getSearchParamsAndDelete = (key) => {
    const params = getSearchParams();
    params.delete(key);

    return params;
}

export const getSearchParamsAndSet = (key, value) => {
    const params = getSearchParams();
    params.set(key, encodeURIComponent(value));

    return params;
}

export const getSearchParams = () => {
    const params = new URLSearchParams(location.search);
    return rewriteParamKeys(params);
};

export const resetPageNumber = (params) => {
    params.delete('page');
    return params;
}

/*
 * Urls constructed in PHP (using `url_encode` in Twig) sometimes result in a query string like:
 * ?dep[0]=Ministerie&dep[1]=AnderMinisterie
 * The keys `dep[0]` and `dep[1]` are rewritten to `dep[]` in this function to make the url functions work as expected.
 */
const rewriteParamKeys = (params) => {
    const removeKeys = [];

    for (const key of params.keys()) {
        const rewrittenKey = rewiteKey(key);
        if (key === rewrittenKey) {
            continue;
        }

        removeKeys.push(key);
        if (params.has(rewrittenKey)) {
            params.append(rewrittenKey, params.get(key));
        } else {
            params.set(rewrittenKey, params.get(key));
        }
    }

    removeKeys.forEach((key) => {
        params.delete(key);
    });

    return params;
}

const rewiteKey = (key) => {
    const regex = /(.+)\[\d+\]$/;
    const [, matchBeforeBrackets] = key.match(regex) || [];

    if (matchBeforeBrackets) {
        // This is a key like `dep[0]` or `dep[1]`, rewrite to `dep[]`
        return `${matchBeforeBrackets}[]`;
    }

    return key;
}

const deleteFromParams = (params, key, value) => {
    const paramValues = params.getAll(key);

    if (!paramValues.length) {
        return params;
    }


    params.delete(key);
    for (const paramValue of paramValues) {
        if (paramValue !== encodeURIComponent(value)) {
            params.append(key, paramValue);
        }
    }

    return params;
};

const appendToParams = (params, key, value) => {
    params.append(key, encodeURIComponent(value));
    return params;
};
