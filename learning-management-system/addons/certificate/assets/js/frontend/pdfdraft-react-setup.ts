import React from 'react';
import ReactDOM from 'react-dom';
import * as ReactJSXRuntime from 'react/jsx-runtime';

(window as any).React = React;
(window as any).ReactDOM = ReactDOM;
(window as any).ReactJSXRuntime = ReactJSXRuntime;

if (!(window as any).wp) {
	(window as any).wp = {};
}
const wp = (window as any).wp;

if (!wp.i18n) {
	wp.i18n = {
		__: (text: string) => text,
		_n: (single: string, plural: string, n: number) =>
			n === 1 ? single : plural,
		_x: (text: string) => text,
		_nx: (single: string, plural: string, n: number) =>
			n === 1 ? single : plural,
		sprintf: (fmt: string, ...args: unknown[]) =>
			fmt.replace(/%s/g, () => String(args.shift() ?? '')),
		isRTL: () => false,
		setLocaleData: () => {},
	};
}

if (!wp.apiFetch) {
	wp.apiFetch = () => Promise.resolve({});
}

if (!wp.mediaUtils) {
	wp.mediaUtils = {};
}
