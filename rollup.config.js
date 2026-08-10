import typescript from '@rollup/plugin-typescript';
import terser from '@rollup/plugin-terser';
import dts from 'rollup-plugin-dts';


function header() {
	return {
		renderChunk(code) {
			return `/*!
 * Client side of Nette Routing.
 *
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
`
				+ code.replace(/\/\*\*.*?\* \*\//s, '');
		},
	};
}

function spaces2tabs() {
	return {
		renderChunk(code) {
			return code.replaceAll('    ', '\t');
		},
	};
}


export default [
	{
		input: 'src/assets/index.ts',
		output: [
			{
				format: 'es',
				dir: 'src/assets/dist',
				entryFileNames: '[name].js',
				generatedCode: 'es2015',
			},
			{
				format: 'es',
				dir: 'src/assets/dist',
				entryFileNames: '[name].min.js',
				generatedCode: 'es2015',
				plugins: [
					terser(),
				],
			},
		],
		plugins: [
			typescript({ compilerOptions: { noEmit: false } }),
			header(),
			spaces2tabs(),
		],
	},

	{
		input: 'src/assets/index.ts',
		output: {
			dir: 'src/assets/dist',
			entryFileNames: '[name].d.ts',
			format: 'es',
		},
		plugins: [
			dts(),
			spaces2tabs(),
		],
	},
];
