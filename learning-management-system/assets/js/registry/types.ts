/**
 * Types for the frontend extension registry.
 */

import type { ReactNode } from 'react';

/**
 * The layout a registered route renders inside.
 *
 * `dashboard-header` places the route within core's `DashboardHeaderLayout`
 * wrapper, exactly as the built-in dashboard routes are placed. `none` renders
 * the route at the top level of the router. Only the admin dashboard has
 * layouts; the account and course-player routers render every route the same
 * way and ignore this.
 */
export type RouteLayout = 'dashboard-header' | 'none';

/**
 * The core app a registration belongs to.
 *
 * Three separate webpack entries, three separate pages, three separate routers
 * — and, because the registry store is one window singleton, one namespace
 * unless routes say which router they are for. Without this an account route
 * would also be rendered by the admin router's catch-all
 * (`layout !== 'dashboard-header'`) branch.
 *
 * Slot fills need no equivalent: a fill can only appear where core renders a
 * `<Slot>` of that name, so the name already carries the app.
 */
export type RegistryApp = 'back-end' | 'account' | 'interactive';

/**
 * A registered entry. Every kind shares an identity and an ordering weight.
 *
 * `id` is what makes registration idempotent: registering the same id twice
 * replaces the first entry in place rather than appending a duplicate.
 *
 * `order` defaults to 10, following the WordPress hook convention. Ties are
 * broken by registration sequence, so ordering is always deterministic.
 */
export interface Entry {
	id: string;
	order?: number;
}

/**
 * A route contributed to the admin router.
 */
export interface RouteEntry extends Entry {
	path: string;
	element: ReactNode;
	layout?: RouteLayout;
	/** Which app's router renders this. Defaults to the admin dashboard. */
	app?: RegistryApp;
}

/**
 * A navigation item contributed to core's dashboard menu.
 *
 * The shape mirrors core's `DASHBOARD_ROUTES` entries so registered items and
 * built-in items are indistinguishable to the components that render them.
 */
export interface MenuItemEntry extends Entry {
	link: string;
	name: string;
	status: string;
}

/**
 * A fill contributed to a named slot.
 *
 * `component` receives whatever props the slot passes at its render site, so a
 * fill behaves like any other component in the tree.
 */
export interface SlotFillEntry<P = any> extends Entry {
	component: React.ComponentType<P>;
}

/**
 * The registry's kinds. Routes are namespaced by app and slots by name, so
 * neither is a fixed member of this union — each is derived from the string
 * that discriminates it.
 */
export type Kind = `route:${string}` | 'menu-item' | `slot:${string}`;

/**
 * A listener notified whenever any kind changes.
 */
export type Listener = () => void;
