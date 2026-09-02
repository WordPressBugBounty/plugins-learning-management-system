import { select } from '@wordpress/data';
import urls from '../../../assets/js/back-end/constants/urls';
import http from '../../../assets/js/back-end/utils/http';

export const getAllAddons = (status: string) => {
	return http({
		path: `${urls.addons}?status=${status}`,
		method: 'get',
	}).then((res: any) => res);
};

export const getAddon = (slug: string) => {
	return http<Addon>({
		path: `${urls.addons}/${slug}`,
		method: 'get',
	}).then((res) => res);
};

export const activateAddon = (slug: string) => {
	return http({
		path: urls.activateAddon,
		method: 'post',
		data: {
			slug: slug,
		},
	}).then((res: any) => res);
};

export const deactivateAddon = (slug: string) => {
	return http({
		path: urls.deactivateAddon,
		method: 'post',
		data: {
			slug: slug,
		},
	}).then((res: any) => res);
};

export const isAddonActive = (slug: string) => {
	try {
		let allAddons = [];
		allAddons = select('addOns').getAddons() as any;
		const currentAddon = allAddons.find((addon: Addon) => addon.slug === slug);
		return currentAddon?.active;
	} catch {
		return false;
	}
};

/**
 * The third-party plugin an addon needs but is not getting, or null.
 *
 * An addon whose `Requires:` header is unmet cannot be activated at all — the
 * REST activation refuses it — so callers use this to keep from asking. Only
 * the addons that report the shortfall set `requirement_fulfilled`, hence the
 * comparison against 'no' rather than a truthiness check.
 */
export const getUnmetRequirement = (slug: string): string | null => {
	try {
		const allAddons = select('addOns').getAddons() as Addons;
		const addon = allAddons.find((a: Addon) => a.slug === slug);

		if (!addon?.requires || 'no' !== addon.requirement_fulfilled) {
			return null;
		}

		return addon.requires;
	} catch {
		return null;
	}
};

export const isIntegrationActive = () => {
	try {
		let allAddons = [];
		allAddons = select('addOns').getAddons() as any;

		const currentAddons = allAddons.filter(
			(addon: Addon) => addon.addon_type === 'integration',
		);
		return currentAddons.some((addon: Addon) => addon?.active);
	} catch {
		return false;
	}
};

const menuPlacementMap: Record<string, string[]> = {
	'course-bundle': ['admin.php?page=masteriyo#/courses/categories'],
	'group-courses': ['admin.php?page=masteriyo#/users/students'],
	'multiple-currency': ['admin.php?page=masteriyo#/settings'],
	zapier: ['admin.php?page=masteriyo#/settings'],
	coupons: ['admin.php?page=masteriyo#/orders'],
	certificate: ['admin.php?page=masteriyo#/quiz-attempts'],
	'course-announcement': [
		'admin.php?page=masteriyo#/gradebook/results',
		'admin.php?page=masteriyo#/question-answers',
	],
	'google-classroom-integration': [
		'admin.php?page=masteriyo#/course-announcements',
		'admin.php?page=masteriyo#/question-answers',
	],
	'google-meet': [
		'admin.php?page=masteriyo#/google-classrooms',
		'admin.php?page=masteriyo#/question-answers',
	],
	zoom: [
		'admin.php?page=masteriyo#/course-announcements',
		'admin.php?page=masteriyo#/gradebook/results',
		'admin.php?page=masteriyo#/question-answers',
	],
};

export const addAndRemoveMenuItem = (addon: AddonResponse) => {
	if (!addon || !Array.isArray(addon.menu_items)) return;

	const menuItem = addon.menu_items.find((item) => item.slug === addon.slug);
	if (!menuItem) return;

	const {
		menu_slug: menuSlug,
		menu_title: menuTitle,
		slug: itemSlug,
	} = menuItem;
	const referenceHrefs = menuPlacementMap[itemSlug];
	if (!referenceHrefs?.length) return;

	let referenceLi: HTMLElement | null = null;

	for (const href of referenceHrefs) {
		const link = document.querySelector(`#adminmenu a[href="${href}"]`);
		if (link) {
			const li = link.closest('li') as HTMLElement | null;
			if (li) {
				referenceLi = li;
				break;
			}
		}
	}

	if (!referenceLi?.parentElement) return;
	const parentUl = referenceLi.parentElement;
	const existing = parentUl.querySelector(`a[href="${menuSlug}"]`);

	const showElement = (el: HTMLElement) => {
		el.style.setProperty('display', 'block', 'important');
		el.style.setProperty('opacity', '1', 'important');
		el.style.setProperty('visibility', 'visible', 'important');
		el.style.removeProperty('pointer-events');
	};

	const hideElement = (el: HTMLElement) => {
		el.style.setProperty('display', 'none', 'important');
		el.style.setProperty('opacity', '0', 'important');
	};

	if (!addon.active) {
		const li = existing?.closest('li') as HTMLElement | null;
		if (li) hideElement(li);
		return;
	}

	if (existing) {
		const li = existing.closest('li') as HTMLElement | null;
		const a = existing as HTMLElement;
		if (li) {
			li.style.setProperty('display', 'list-item', 'important');
			li.style.setProperty('opacity', '1', 'important');
		}
		showElement(a);
		return;
	}

	const newLi = document.createElement('li');
	const newA = document.createElement('a');
	newA.href = menuSlug;
	newA.textContent = menuTitle;
	newLi.appendChild(newA);

	if (referenceLi.nextSibling) {
		parentUl.insertBefore(newLi, referenceLi.nextSibling);
	} else {
		parentUl.appendChild(newLi);
	}

	newLi.style.setProperty('display', 'list-item', 'important');
	newLi.style.setProperty('opacity', '1', 'important');
	showElement(newA);
};

export const bulkAddAndRemoveMenuItem = (addons_data: AddonsResponse) => {
	if (!addons_data || !addons_data.data || !addons_data.menu_items) return;

	addons_data.data.forEach((addon) => {
		addAndRemoveMenuItem({ ...addon, menu_items: addons_data.menu_items });
	});
};

export const onMenuItemClick = (adminUrl: string, menuLink: string): void => {
	const fullUrl: string = `${adminUrl}${menuLink}`;

	const menuItem: HTMLAnchorElement | null = document.querySelector(
		`li a[href="${menuLink}"]`,
	);

	if (menuItem) {
		const parentLi: HTMLLIElement | null = menuItem.closest('li');
		if (parentLi) {
			parentLi.classList.add('current');
		}
	}

	window.open(fullUrl, '_self');
};
