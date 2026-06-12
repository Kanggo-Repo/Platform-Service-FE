/* eslint-disable no-var */
(function () {
    var endpoint = '/api/v1/number-helper/format';
    var cache = new Map();
    var pending = new Map();
    var ACCURACY_DECIMALS = 11;

    function toNumber(value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }
        var num = Number(value);
        return Number.isFinite(num) ? num : null;
    }

    function normalizeItem(item) {
        var options = item || {};
        return {
            key: options.key,
            value: toNumber(options.value),
            decimals: options.decimals === '' || options.decimals === null || options.decimals === undefined
                ? null
                : Number(options.decimals),
            decimal_separator: options.decimalSeparator || options.decimal_separator || ',',
            thousands_separator: options.thousandsSeparator || options.thousands_separator || '.',
            allowEmpty: options.allowEmpty !== false,
        };
    }

    function buildKey(item) {
        return JSON.stringify({
            value: item.value,
            decimals: item.decimals,
            decimal_separator: item.decimal_separator,
            thousands_separator: item.thousands_separator,
        });
    }

    function emptyResult() {
        return { formatted: '', plain: '', normalized: 0 };
    }

    function fallbackResult(value) {
        var num = toNumber(value);
        if (num === null) {
            return emptyResult();
        }
        var plain = String(num);
        return { formatted: plain, plain: plain, normalized: num };
    }

    function requestFormat(payload) {
        return fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        }).then(function (response) {
            return response.json();
        });
    }

    function formatValues(items) {
        var results = {};
        var missing = [];

        (items || []).forEach(function (item) {
            var normalized = normalizeItem(item);
            if (!normalized.key) {
                return;
            }

            if (normalized.value === null && normalized.allowEmpty) {
                results[normalized.key] = emptyResult();
                return;
            }

            var cacheKey = buildKey(normalized);
            if (cache.has(cacheKey)) {
                results[normalized.key] = cache.get(cacheKey);
                return;
            }

            missing.push(normalized);
        });

        if (missing.length === 0) {
            return Promise.resolve(results);
        }

        var requestKey = JSON.stringify(missing.map(function (item) {
            return buildKey(item);
        }));

        if (pending.has(requestKey)) {
            return pending.get(requestKey).then(function (response) {
                Object.keys(response).forEach(function (key) {
                    results[key] = response[key];
                });
                return results;
            });
        }

        var request = requestFormat({ values: missing })
            .then(function (response) {
                if (!response || !response.success) {
                    throw new Error(response && response.message ? response.message : 'Format failed');
                }
                var data = response.data || {};
                missing.forEach(function (item) {
                    var cacheKey = buildKey(item);
                    var formatted = data[item.key] || fallbackResult(item.value);
                    cache.set(cacheKey, formatted);
                    results[item.key] = formatted;
                });
                return results;
            })
            .catch(function () {
                missing.forEach(function (item) {
                    results[item.key] = fallbackResult(item.value);
                });
                return results;
            })
            .finally(function () {
                pending.delete(requestKey);
            });

        pending.set(requestKey, request);
        return request;
    }

    function formatValue(value, options) {
        return formatValues([Object.assign({ key: 'value', value: value }, options || {})])
            .then(function (results) {
                return results.value || emptyResult();
            });
    }

    // Local synchronous twin of PHP NumberHelper::accurate(): round to the
    // accuracy standard (11 decimals) and return a Number. Use this for any
    // client-side calculation that must match what the backend stores.
    function accurate(value, decimals) {
        var num = toNumber(value);
        if (num === null) {
            return 0;
        }
        var d = decimals === undefined || decimals === null ? ACCURACY_DECIMALS : Number(decimals);
        if (!Number.isFinite(d) || d < 0) {
            d = ACCURACY_DECIMALS;
        }
        var factor = Math.pow(10, d);
        return Math.round((num + Number.EPSILON) * factor) / factor;
    }

    window.NumberHelperClient = {
        ACCURACY_DECIMALS: ACCURACY_DECIMALS,
        accurate: accurate,
        formatValues: formatValues,
        formatValue: formatValue,
    };
})();
