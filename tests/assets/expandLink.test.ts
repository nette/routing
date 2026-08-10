import { expect, test } from 'vitest';
import { expandLink } from '../../src/assets/index';
import cases from './expandLink.cases.json';

for (const item of cases.expand) {
	test(item.name, () => {
		expect(expandLink(item.template, item.values as Record<string, string | number | boolean | null>)).toBe(item.expected);
	});
}

for (const item of cases.errors) {
	test(item.name, () => {
		expect(() => expandLink(item.template, item.values as Record<string, string | number | boolean | null>)).toThrow();
	});
}
