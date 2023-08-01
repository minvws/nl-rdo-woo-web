const remove = (params, key, value) => {
    const values = params.getAll(key)

    if (!values.length) {
        return;
    }

    params.delete(key);
    for (const v of values) {
        if (v !== encodeURIComponent(value)) {
            params.append(key, v);
        }
    }
};

const append = (params, key, value) => {
    params.append(key, encodeURIComponent(value));
};

const updateQueryString = (checked, key, value) => {
    const params = new URLSearchParams(location.search);
    if (checked) {
        append(params, key, value);
    } else {
        remove(params, key, value);
    }

    return params;
};

const removeQueryString = (key) => {
    const params = new URLSearchParams(location.search);
    params.delete(key);

    return params;
}

const replaceQueryString = (key, value) => {
    const params = new URLSearchParams(location.search);
    params.set(key, encodeURIComponent(value));

    return params;
}

window.toggleDateFacet = (el) => {
    var params;

    if (Date.parse(el.value)) {
        params = replaceQueryString(el.name, el.value)
    } else {
        params = removeQueryString(el.name)
    }

    window.history.replaceState([], '', `${location.pathname}?${params}`);

    updateResults(params);
}

window.toggleFacet = (el) => {
    const params = updateQueryString(el.checked, el.name, el.value)
    window.history.replaceState({}, '', `${location.pathname}?${params}`);

    updateResults(params);
}

window.setFacet = (el) => {
    const params = replaceQueryString(el.name, el.value)
    window.history.replaceState({}, '', `${location.pathname}?${params}`);

    updateResults(params);
}

window.removeFacetPill = (el) => {
    // Remove pill
    el.remove();

    // update the query string and reload
    var params;
    if (el.dataset.value === '') {
        params = removeQueryString(el.dataset.key);
    } else {
        params = updateQueryString(false, el.dataset.key, el.dataset.value);
    }
    window.history.replaceState({}, '', `${location.pathname}?${params}`);

    updateResults(params);
}

window.updateResults = (params) => {
    el = document.getElementById('js-results');
    if (!el) {
        return;
    }

    fetch(`/_result?${params}`)
        .then(response => response.text())
        .then(json => {
            const data = JSON.parse(json);
            document.getElementById('js-facets').innerHTML = JSON.parse(data.facets);
            document.getElementById('js-results').innerHTML = JSON.parse(data.results);
        })
    ;
}
