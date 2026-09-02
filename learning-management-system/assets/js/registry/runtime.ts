/**
 * Core's runtime surface for the pro bundle.
 *
 * The pro bundle is a separate webpack compilation, so it does **not** share
 * module instances with core. That is fine for stateless libraries — a second
 * copy of `react-hook-form` behaves identically — but fatal for anything that
 * carries React context:
 *
 * - a second `@chakra-ui/react` means a second `ChakraContext` and a second
 *   emotion cache, so a registered component would not see core's theme;
 * - a second `@tanstack/react-query` means `useQuery` cannot find core's
 *   `QueryClientProvider` and throws;
 * - a second `react-router-dom` means `useNavigate` cannot find core's router;
 * - a second `react-hook-form` means `useFormContext` returns null, which is
 *   how every settings-panel fill reads the form it is rendered inside;
 * - a second `better-react-mathjax` means `<MathJax>` cannot find the
 *   `MathJaxContext` every core app mounts, and throws "MathJax was not
 *   loaded" into the error boundary;
 * - a second `@backend/hooks/useMasteriyoPlayer` means a pro-rendered
 *   `MasteriyoPlayer` (quiz video/audio answers, assignment videos) cannot
 *   find the provider core mounts and throws "useMasteriyoPlayer must be
 *   used within a MasteriyoPlayerProvider".
 *
 * The same applies to a module that registers something *once per page*
 * rather than carrying a context. The block editor is deliberately *not*
 * such a module any more: `components/common/StandaloneEditor` registers
 * nothing global, so the pro bundles that render it may safely compile their
 * own copy, and it needs no entry here.
 *
 * So core publishes them here and the pro webpack config externalises them onto
 * this object. React itself needs no entry: it is
 * already externalised to `window.React` by
 * `@wordpress/dependency-extraction-webpack-plugin` in both compilations, and
 * `@wordpress/*` likewise. `@emotion/react` needs none either — every Chakra
 * component pro renders comes from core's instance and therefore uses core's
 * emotion cache; no pro-reachable module imports emotion directly.
 *
 * The registry deliberately has no entry either. Its *state* lives on a window
 * singleton (see `store.ts`), so both bundles may hold their own copy of the
 * module and still share one store.
 *
 * Keep this list to the modules that carry context. Every addition is a
 * versioned promise to whatever pro bundle is on the page.
 *
 * The six below are published by *every* core app, because every app's pro
 * bundle may render Chakra, run a query, read a form, navigate, set math or
 * play media. A module only one app needs is passed in by that app instead —
 * see the `extra` parameter — so the account and course-player pages do not
 * pay for it.
 */

import * as masteriyoPlayer from '@backend/hooks/useMasteriyoPlayer';
import * as chakra from '@chakra-ui/react';
import * as reactQuery from '@tanstack/react-query';
import * as mathjax from 'better-react-mathjax';
import * as reactHookForm from 'react-hook-form';
import * as router from 'react-router-dom';

/**
 * The property core publishes on. Referenced by name in
 * `webpack/webpack.config.prod.js`'s pro configuration — change both together.
 */
export const RUNTIME_KEY = 'masteriyoRuntime';

/**
 * Bumped when a module is removed from the surface or replaced incompatibly.
 * Adding a module does not require a bump.
 *
 * 2: `isolatedBlockEditor` left the surface — the editor is core's own
 *    StandaloneEditor now, compiled into every bundle that renders it.
 */
export const RUNTIME_VERSION = 2;

/**
 * Publish core's shared modules for the pro bundle.
 *
 * Called from each entry point before it renders, so the surface is in place by
 * the time the pro bundle — enqueued as a dependent of core's script — runs.
 *
 * @param extra Modules only the calling app publishes, keyed by the property
 *              the pro webpack config externalises them onto. Passed in rather
 *              than imported here so an app that does not need one does not
 *              bundle it: the account page context is the account app's
 *              alone, and importing it here would bundle it into the admin
 *              dashboard and course player too.
 */
export function exposeRuntime(extra: Record<string, unknown> = {}): void {
	const host = globalThis as unknown as Record<string, any>;

	// First-wins, matching the PHP container and the registry store. Two core
	// bundles on one page would otherwise hand pro a second set of instances
	// and undo the point of this file.
	if (host[RUNTIME_KEY]) {
		return;
	}

	host[RUNTIME_KEY] = {
		version: RUNTIME_VERSION,
		chakra,
		masteriyoPlayer,
		mathjax,
		reactHookForm,
		reactQuery,
		router,
		...extra,
	};
}
