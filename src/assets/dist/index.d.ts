/**
 * Expands a URL template created by Nette LinkGenerator. Values are encoded as
 * the server would at the same position; unexpanded query variables drop with
 * their pair, an unexpanded path variable or an unknown name throws. An array
 * value in a query variable expands to multiple pairs like the router would.
 *
 * This file is part of the Nette Framework (https://nette.org)
 */
type ExpandValue = string | number | boolean | null | undefined | readonly ExpandValue[] | {
	readonly [key: string]: ExpandValue;
};
declare function expandLink(template: string, values?: Record<string, ExpandValue>): string;

export { expandLink };
export type { ExpandValue };
