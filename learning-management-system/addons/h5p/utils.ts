import { getUnmetRequirement, isAddonActive } from '@addons/add-ons/api/addons';
import { isLicensePlanActive } from '../../assets/js/back-end/utils/utils';

/**
 * Whether the H5P plugin this addon integrates with is installed and active.
 *
 * The addon's `main.php` returns before registering a single service provider
 * when the plugin is missing, so an install with the addon active but the plugin
 * gone has no H5P routes at all — not even the ones the addon's own product
 * ships. Calling one answers "No route was found matching the URL and request
 * method", which tells nobody what to go and do.
 */
export const isH5PPluginActive = (): boolean =>
	null === getUnmetRequirement('h5p');

/**
 * Whether the H5P *quiz* feature is usable.
 *
 * Three things have to line up, and each fails differently:
 *
 * - The addon itself is shared and ships to both products — what it gives the
 *   free product is the content renderer (`Compatibility/H5PRenderer.php`).
 *   Every part of the H5P *quiz* backend lives under `addons/h5p/pro/`, so in
 *   the free product the addon is active and `masteriyo/pro/v1/h5p-quizzes`
 *   does not exist. Hence the licence check.
 * - The H5P plugin has to be there, or the addon registers nothing at all.
 * - The addon has to be switched on.
 *
 * `isAddonActive('h5p')` alone therefore renders H5P quiz UI that queries routes
 * which are not registered.
 */
export const isH5PQuizAvailable = (): boolean =>
	isAddonActive('h5p') && isH5PPluginActive() && isLicensePlanActive();
