/*!
 * Client side of Nette Routing.
 *
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
/**
 * Expands a URL template created by Nette LinkGenerator. Values are encoded as
 * the server would at the same position; unexpanded query variables drop with
 * their pair, an unexpanded path variable or an unknown name throws. An array
 * value in a query variable expands to multiple pairs like the router would.
 *
 * This file is part of the Nette Framework (https://nette.org)
 */
function encodePathSegment(value) {
	return value.replace(/[^0-9A-Za-z_.~!$&'()*+,;=:@/-]/g, encodeURIComponent);
}
function toValueString(value) {
	return typeof value === 'boolean'
		? (value ? '1' : '0') // like the router: false is '0', not 'false'
		: String(value);
}
// mirrors PHP http_build_query(): bracketed keys, null members skipped
function flattenQuery(key, value, pairs) {
	if (typeof value === 'object') {
		for (let [k, v] of Object.entries(value)) {
			if (v !== null && v !== undefined) {
				flattenQuery(`${key}%5B${encodeURIComponent(k)}%5D`, v, pairs);
			}
		}
	}
	else {
		pairs.push(key + '=' + encodeURIComponent(toValueString(value)));
	}
}
function expandLink(template, values = {}) {
	let fragment = '';
	let hash = template.indexOf('#');
	if (hash > -1) {
		fragment = template.slice(hash);
		template = template.slice(0, hash);
	}
	let path = template;
	let pairs = [];
	let separator = '&'; // server-configurable; detected from the template, values cannot contain a raw & or ;
	let question = template.indexOf('?');
	if (question > -1) {
		path = template.slice(0, question);
		let query = template.slice(question + 1);
		separator = query.match(/[&;]/)?.[0] ?? '&';
		pairs = query.split(/[&;]/);
	}
	for (let [name, value] of Object.entries(values)) {
		if (value === null || value === undefined) {
			continue; // unexpanded
		}
		let variable = `{${name}}`;
		let pos = pairs.findIndex((pair) => pair.endsWith('=' + variable));
		if (path.includes(variable)) {
			if (typeof value === 'object') {
				throw new Error(`expandLink: value of '${name}' must be a scalar`);
			}
			path = path.replace(variable, encodePathSegment(toValueString(value)));
		}
		else if (pos > -1) {
			if (typeof value === 'object') {
				let expanded = [];
				flattenQuery(pairs[pos].slice(0, -(variable.length + 1)), value, expanded);
				pairs.splice(pos, 1, ...expanded); // an empty array drops the pair
			}
			else {
				pairs[pos] = pairs[pos].slice(0, -variable.length) + encodeURIComponent(toValueString(value));
			}
		}
		else {
			throw new Error(`expandLink: unknown variable '${name}'`);
		}
	}
	if (/\{[A-Za-z_]\w*\}/.test(path)) {
		throw new Error(`expandLink: unexpanded path variable in '${path}'`);
	}
	let query = pairs
		.filter((pair) => !/=\{[A-Za-z_]\w*\}$/.test(pair))
		.join(separator);
	return path + (query ? '?' + query : '') + fragment;
}

export { expandLink };
