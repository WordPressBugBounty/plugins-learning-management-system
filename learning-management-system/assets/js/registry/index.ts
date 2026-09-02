/**
 * The frontend extension registry — the seam through which pro contributes UI.
 *
 * Core renders routes, admin menu items and named slot content from here and
 * never imports pro code. Pro registers into it from its own bundle, which is
 * enqueued only when pro is active.
 *
 * ```ts
 * // pro/assets/js/back-end/index.tsx
 * registerRoute({ id: 'license', path: '/license', element: <License /> });
 * registerMenuItem({ id: 'license', link: '/license', name: 'License', status: 'license' });
 * registerSlotFill('settings.authentication.two-factor', { id: '2fa', component: TwoFactor });
 * ```
 */

import { getEntries, register, routeKind, unregister } from './store';
import type {
	MenuItemEntry,
	RegistryApp,
	RouteEntry,
	SlotFillEntry,
} from './types';

export {
	Slot,
	useRegistered,
	useRegisteredMenuItems,
	useRegisteredRoutes,
	useSlotFills,
} from './react';
export type { SlotProps } from './react';
export {
	API_VERSION,
	DEFAULT_APP,
	DEFAULT_ORDER,
	resetRegistry,
	routeKind,
	subscribe,
} from './store';
export type {
	Entry,
	Kind,
	Listener,
	MenuItemEntry,
	RegistryApp,
	RouteEntry,
	RouteLayout,
	SlotFillEntry,
} from './types';

/**
 * Contribute a route to one of core's routers.
 *
 * @param route The route. `app` defaults to the admin dashboard and `layout`
 *              to `none`.
 * @return An unregister function.
 */
export function registerRoute(route: RouteEntry): () => void {
	return register(routeKind(route.app), route);
}

/**
 * Remove a contributed route.
 *
 * @param id  The route's id.
 * @param app The app it was registered for. Defaults to the admin dashboard.
 * @return Whether anything was removed.
 */
export function unregisterRoute(id: string, app?: RegistryApp): boolean {
	return unregister(routeKind(app), id);
}

/**
 * An app's contributed routes, in order. Prefer `useRegisteredRoutes` inside a
 * component — this is for callers that are not rendering.
 *
 * @param app The app. Defaults to the admin dashboard.
 * @return The ordered routes.
 */
export function getRoutes(app?: RegistryApp): ReadonlyArray<RouteEntry> {
	return getEntries<RouteEntry>(routeKind(app));
}

/**
 * Contribute an item to core's dashboard menu.
 *
 * @param item The menu item.
 * @return An unregister function.
 */
export function registerMenuItem(item: MenuItemEntry): () => void {
	return register('menu-item', item);
}

/**
 * Remove a contributed menu item.
 *
 * @param id The item's id.
 * @return Whether anything was removed.
 */
export function unregisterMenuItem(id: string): boolean {
	return unregister('menu-item', id);
}

/**
 * The contributed menu items, in order.
 *
 * @return The ordered menu items.
 */
export function getMenuItems(): ReadonlyArray<MenuItemEntry> {
	return getEntries<MenuItemEntry>('menu-item');
}

/**
 * Contribute a fill to a named slot.
 *
 * @param name The slot name.
 * @param fill The fill.
 * @return An unregister function.
 */
export function registerSlotFill<P = any>(
	name: string,
	fill: SlotFillEntry<P>,
): () => void {
	return register(`slot:${name}`, fill);
}

/**
 * Remove a fill from a named slot.
 *
 * @param name The slot name.
 * @param id   The fill's id.
 * @return Whether anything was removed.
 */
export function unregisterSlotFill(name: string, id: string): boolean {
	return unregister(`slot:${name}`, id);
}

/**
 * The fills of a named slot, in order.
 *
 * @param name The slot name.
 * @return The ordered fills.
 */
export function getSlotFills<P = any>(
	name: string,
): ReadonlyArray<SlotFillEntry<P>> {
	return getEntries<SlotFillEntry<P>>(`slot:${name}`);
}
