/**
 * React bindings for the extension registry.
 *
 * Written with `React.createElement` rather than JSX so this module stays
 * type-strippable and can be required directly by `node --test`. The registry
 * is the seam the whole frontend separation rests on, so it is worth having its
 * rendering under test in a repo with no DOM test harness.
 */

import React from 'react';
import { getEntries, routeKind, subscribe } from './store';
import type {
	Entry,
	Kind,
	MenuItemEntry,
	RegistryApp,
	RouteEntry,
	SlotFillEntry,
} from './types';

/**
 * The ordered entries of a kind, re-rendering the caller when they change.
 *
 * This is what makes a registration arriving *after* the initial render visible
 * rather than silently dropped — which is the normal case, because the pro
 * bundle is enqueued as a dependent of core's and therefore executes after
 * core has already mounted.
 *
 * @param kind The kind to read.
 * @return The ordered entries.
 */
export function useRegistered<T extends Entry>(kind: Kind): ReadonlyArray<T> {
	const getSnapshot = React.useCallback(() => getEntries<T>(kind), [kind]);

	// The server snapshot is the same snapshot: the registry has no
	// server-rendered form, and `getEntries` is pure.
	return React.useSyncExternalStore(subscribe, getSnapshot, getSnapshot);
}

/**
 * An app's contributed routes, in order, re-rendering the router when they
 * change.
 *
 * @param app The app whose router is asking. Defaults to the admin dashboard.
 * @return The ordered routes.
 */
export function useRegisteredRoutes(
	app?: RegistryApp,
): ReadonlyArray<RouteEntry> {
	return useRegistered<RouteEntry>(routeKind(app));
}

/**
 * The contributed dashboard menu items, in order.
 *
 * @return The ordered menu items.
 */
export function useRegisteredMenuItems(): ReadonlyArray<MenuItemEntry> {
	return useRegistered<MenuItemEntry>('menu-item');
}

/**
 * The fills registered for a named slot, in order.
 *
 * @param name The slot name.
 * @return The ordered fills.
 */
export function useSlotFills<P = any>(
	name: string,
): ReadonlyArray<SlotFillEntry<P>> {
	return useRegistered<SlotFillEntry<P>>(`slot:${name}`);
}

/**
 * Props for {@link Slot}.
 *
 * Everything other than `name` and `children` is forwarded to each fill, so a
 * fill is an ordinary component receiving ordinary props.
 */
export interface SlotProps {
	name: string;
	/**
	 * Rendered when the slot has no fills — the free product's version of
	 * whatever pro contributes here. Omit it and an unfilled slot renders
	 * nothing.
	 */
	children?: React.ReactNode;
	[prop: string]: any;
}

/**
 * A named insertion point.
 *
 * ```tsx
 * <Slot name="settings.authentication.two-factor" formContext={methods} />
 * ```
 *
 * @param props The slot name, an optional unfilled fallback, and the props to
 *              forward to each fill.
 * @return The fills in order, or the fallback, or null.
 */
export const Slot: React.FC<SlotProps> = (props) => {
	const { name, children, ...forwarded } = props;
	const fills = useSlotFills(name);

	if (0 === fills.length) {
		return (children ?? null) as React.ReactElement | null;
	}

	return React.createElement(
		React.Fragment,
		null,
		...fills.map((fill) =>
			React.createElement(fill.component, { ...forwarded, key: fill.id }),
		),
	);
};
