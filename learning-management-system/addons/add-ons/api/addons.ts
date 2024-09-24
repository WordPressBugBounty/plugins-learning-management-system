import { select } from '@wordpress/data';

export const isAddonActive = (slug: string) => {
	try {
		let allAddons = [];
		allAddons = select('addOns').getAddons() as any;
		const currentAddon = allAddons.find((addon: Addon) => addon.slug === slug);
		return currentAddon?.active;
	} catch (error) {
		return false;
	}
};
