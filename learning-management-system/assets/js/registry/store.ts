/**
 * The frontend extension registry's store.
 *
 * Pro contributes UI to core through this store: routes, admin menu items and
 * named slot fills. Core renders from the store; it never imports pro code.
 *
 * Two properties this file exists to guarantee:
 *
 * 1. **One store per page, across bundles.** Core and pro are separate webpack
 *    compilations, so the module below is instantiated once per bundle. The
 *    state is therefore held on a window singleton rather than in module scope,
 *    and every instance of this module operates on the same store.
 *
 * 2. **Referentially stable snapshots.** `getEntries` is read by
 *    `useSyncExternalStore`, which compares snapshots by identity and loops
 *    forever if a fresh array is returned on every call. Sorted results are
 *    cached per kind and the cache entry is dropped only when that kind
 *    actually changes.
 *
 * Deliberately free of JSX and of any React import, so it runs under
 * `node --test` with Node's type stripping and needs no DOM.
 */

import type { Entry, Kind, Listener, RegistryApp } from './types';

/**
 * The property the store hangs off. Matches the existing
 * `window.customFieldRegistry` precedent in
 * `assets/js/admin/masteriyo-builder-custom-fields.js`.
 */
export const GLOBAL_KEY = '__MASTERIYO_REGISTRY__';

/**
 * Bumped only when the store's shape changes in a way that makes a mismatched
 * pair of bundles unsafe. A mismatch is reported rather than papered over: the
 * first bundle to load wins, mirroring the PHP container's first-wins rule.
 */
export const API_VERSION = 1;

/**
 * The default ordering weight, following the WordPress hook convention.
 */
export const DEFAULT_ORDER = 10;

/**
 * The app a route belongs to when it does not say — the admin dashboard, which
 * was the only app the registry served when it was introduced. Existing
 * registrations therefore keep working unchanged.
 */
export const DEFAULT_APP: RegistryApp = 'back-end';

/**
 * The store kind routes of an app are held under.
 *
 * One function rather than the string spelled at each call site, because the
 * registering side and the reading side must agree exactly and they live in
 * different bundles.
 *
 * @param app The app. Defaults to the admin dashboard.
 * @return The kind.
 */
export function routeKind(app: RegistryApp = DEFAULT_APP): Kind {
	return `route:${app}`;
}

interface Held<T extends Entry> {
	entry: T;
	/** Registration sequence, used to break ordering ties deterministically. */
	seq: number;
}

interface Store {
	version: number;
	entries: Map<string, Array<Held<any>>>;
	snapshots: Map<string, ReadonlyArray<any>>;
	listeners: Set<Listener>;
	seq: number;
	/** Whether an API version mismatch has already been reported. */
	warned?: boolean;
}

/**
 * Somewhere to hang the store. `globalThis` in a browser is `window`; under
 * `node --test` it is the Node global, which is what makes this testable.
 */
type Host = Record<string, any>;

const host = (): Host => globalThis as unknown as Host;

const create = (): Store => ({
	version: API_VERSION,
	entries: new Map(),
	snapshots: new Map(),
	listeners: new Set(),
	seq: 0,
});

/**
 * The store for this page, creating it if this is the first bundle to ask.
 */
export function getStore(): Store {
	const h = host();
	const existing = h[GLOBAL_KEY] as Store | undefined;

	if (existing) {
		// Reported once, not on every read: `getStore` is called by every
		// registration and every snapshot read, so warning each time would bury
		// the message it is trying to deliver.
		if (existing.version !== API_VERSION && !existing.warned) {
			existing.warned = true;
			// Not thrown: a version mismatch means two bundles built at
			// different times are on the page, and refusing to run would take
			// the whole admin screen down over a contribution that may not even
			// be reachable. Loud, and first-wins.

			console.error(
				`[masteriyo] Registry API version mismatch: the page already has v${existing.version} and this bundle expects v${API_VERSION}. Rebuild both bundles. Using v${existing.version}.`,
			);
		}
		return existing;
	}

	const store = create();
	h[GLOBAL_KEY] = store;
	return store;
}

const listOf = (store: Store, kind: Kind): Array<Held<any>> => {
	let list = store.entries.get(kind);
	if (!list) {
		list = [];
		store.entries.set(kind, list);
	}
	return list;
};

const invalidate = (store: Store, kind: Kind): void => {
	store.snapshots.delete(kind);
};

const notify = (store: Store): void => {
	// Copied before iterating: a listener that unsubscribes during
	// notification must not perturb this pass.
	for (const listener of Array.from(store.listeners)) {
		listener();
	}
};

/**
 * Register an entry under a kind.
 *
 * Registering an id that is already present **replaces it in place**, keeping
 * its position among equally-weighted siblings. That is what makes a
 * replacement a replacement rather than a move to the end of the list.
 *
 * @param kind  The kind to register under.
 * @param entry The entry. Must carry a non-empty `id`.
 * @return An unregister function for this entry.
 */
export function register<T extends Entry>(kind: Kind, entry: T): () => void {
	if (!entry || typeof entry.id !== 'string' || '' === entry.id) {
		throw new Error(
			`[masteriyo] Registry: an entry registered under "${kind}" has no id.`,
		);
	}

	const store = getStore();
	const list = listOf(store, kind);
	const at = list.findIndex((held) => held.entry.id === entry.id);

	if (-1 === at) {
		list.push({ entry, seq: store.seq++ });
	} else {
		// Keep the original sequence so the replacement does not jump position.
		list[at] = { entry, seq: list[at].seq };
	}

	invalidate(store, kind);
	notify(store);

	return () => unregister(kind, entry.id);
}

/**
 * Remove an entry. Removing an id that is not registered is a no-op and does
 * not notify — an unregister function may be called twice.
 *
 * @param kind The kind the entry was registered under.
 * @param id   The entry's id.
 * @return Whether anything was removed.
 */
export function unregister(kind: Kind, id: string): boolean {
	const store = getStore();
	const list = store.entries.get(kind);
	if (!list) {
		return false;
	}

	const at = list.findIndex((held) => held.entry.id === id);
	if (-1 === at) {
		return false;
	}

	list.splice(at, 1);
	invalidate(store, kind);
	notify(store);
	return true;
}

/**
 * The entries of a kind, ordered by `order` then registration sequence.
 *
 * The returned array is referentially stable until the kind changes, so it is
 * safe to hand to `useSyncExternalStore`. It is frozen, because a caller that
 * sorted or spliced it in place would corrupt every other reader's view.
 *
 * @param kind The kind to read.
 * @return The ordered entries. Empty when nothing is registered.
 */
export function getEntries<T extends Entry>(kind: Kind): ReadonlyArray<T> {
	const store = getStore();

	const cached = store.snapshots.get(kind);
	if (cached) {
		return cached as ReadonlyArray<T>;
	}

	const list = store.entries.get(kind) ?? [];
	const sorted = list
		.slice()
		.sort((a, b) => {
			const byOrder =
				(a.entry.order ?? DEFAULT_ORDER) - (b.entry.order ?? DEFAULT_ORDER);
			return 0 !== byOrder ? byOrder : a.seq - b.seq;
		})
		.map((held) => held.entry);

	const snapshot = Object.freeze(sorted) as ReadonlyArray<T>;
	store.snapshots.set(kind, snapshot);
	return snapshot;
}

/**
 * Subscribe to every change, in any kind.
 *
 * Coarse by design. The registry holds tens of entries, not thousands, and a
 * per-kind subscription would buy nothing but a way to miss a notification.
 *
 * @param listener Called after each change.
 * @return An unsubscribe function.
 */
export function subscribe(listener: Listener): () => void {
	const store = getStore();
	store.listeners.add(listener);
	return () => {
		store.listeners.delete(listener);
	};
}

/**
 * Drop every registration and listener.
 *
 * For tests. Nothing in the products calls this — a contribution is removed by
 * unregistering it, not by resetting the world.
 */
export function resetRegistry(): void {
	delete host()[GLOBAL_KEY];
}
