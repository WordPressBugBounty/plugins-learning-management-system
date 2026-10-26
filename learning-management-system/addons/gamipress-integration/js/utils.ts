import { isEmpty } from '../../../assets/js/back-end/utils/utils';
import { AccountPageLocation, PlacementPage, RewardType } from './enums/enums';

export const SIDEBAR_LOCATIONS: string[] = [
	AccountPageLocation.BELOW_USERNAME,
	AccountPageLocation.BELOW_AUTHOR_SECTION,
];

export const makeUiPlacementRouteSlug = (placement: UiPlacementData) => {
	let titleSlug = '';

	if (placement.title) {
		titleSlug = placement?.title?.toLowerCase()?.replaceAll(' ', '-') + '-';
	}
	return titleSlug + placement?.id;
};

export const makeUiPlacementAccountPageRoute = (placement: UiPlacementData) => {
	return '/' + makeUiPlacementRouteSlug(placement);
};

/**
 * The point types without those an account sidebar placement already shows.
 */
export const omitSidebarPointTypes = <T>(
	pointTypes: Record<string, T> | undefined,
	uiPlacements: UiPlacementData[] = [],
): Record<string, T> | undefined => {
	if (!pointTypes) {
		return pointTypes;
	}

	const sidebarPlacements = uiPlacements.filter(
		(placement) =>
			placement.page === PlacementPage.ACCOUNT_PAGE &&
			placement.reward_type === RewardType.POINT &&
			SIDEBAR_LOCATIONS.includes(placement.location ?? ''),
	);

	if (sidebarPlacements.some((placement) => isEmpty(placement.types))) {
		return {};
	}

	const shownTypes = sidebarPlacements.flatMap(
		(placement) => placement.types ?? [],
	);

	return Object.fromEntries(
		Object.entries(pointTypes).filter(
			([pointType]) => !shownTypes.includes(pointType),
		),
	);
};
