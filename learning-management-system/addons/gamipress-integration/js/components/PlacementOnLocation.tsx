import { Flex, Stack, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { isEmpty } from '../../../../assets/js/back-end/utils/utils';
import { AccountPageLocation, RewardType } from '../enums/enums';

type PointTypeData = NonNullable<Props['pointTypes']>[string];
type RankTypeData = NonNullable<Props['rankTypes']>[string];
type AchievementTypeData = NonNullable<Props['achievementTypes']>[string];

interface Props {
	location: string | string[];
	page?: string;
	placementId?: string;
	showTypeHeading?: boolean;
	renderPoint: (
		pointTypeData: PointTypeData,
		placement: UiPlacementData,
	) => React.ReactNode;
	renderRank: (
		rankTypeData: RankTypeData,
		placement: UiPlacementData,
	) => React.ReactNode;
	renderAchievement: (
		achievementData: AchievementTypeData['achievements'][number],
		achievementTypeData: AchievementTypeData,
		placement: UiPlacementData,
	) => React.ReactNode;
	wrapItems?: (
		items: React.ReactNode[],
		placement: UiPlacementData,
	) => React.ReactNode;
	wrapPlacementGroup?: (
		contents: React.ReactNode[],
		placement: UiPlacementData,
	) => React.ReactNode;
	render?: (contents: React.ReactNode[]) => React.ReactNode;

	uiPlacements?: UiPlacementData[];
	pointTypes?: {
		[pointType: string]: {
			ID: any;
			singular_name: string;
			plural_name: string;
			points: number;
			image_url: string;
		};
	};
	rankTypes?: {
		[rankType: string]: {
			ID: any;
			singular_name: string;
			plural_name: string;
			rank: string;
			image_url: string;
		};
	};
	achievementTypes?: {
		[achievementType: string]: {
			ID: any;
			singular_name: string;
			plural_name: string;
			achievements: {
				label: string;
				image_url: string;
			}[];
		};
	};
}

const NON_SIDEBAR_LOCATIONS: string[] = [
	AccountPageLocation.NEW_TAB,
	AccountPageLocation.DASHBOARD_NEW_SECTION,
	AccountPageLocation.DASHBOARD_CARD,
];

const PlacementOnLocation: React.FC<Props> = (props) => {
	const {
		page,
		location,
		showTypeHeading = true,
		renderPoint,
		renderRank,
		renderAchievement,
		wrapItems = (items, placement) => {
			const isInsideSidebar = !NON_SIDEBAR_LOCATIONS.includes(
				placement.location ?? '',
			);

			return (
				<Stack
					width={'full'}
					spacing={isInsideSidebar ? 3 : 6}
					direction={isInsideSidebar ? 'column' : 'row'}
				>
					{items}
				</Stack>
			);
		},
		wrapPlacementGroup = (contents) => contents,
		render = (contents) => contents,
		placementId,
		pointTypes,
		rankTypes,
		achievementTypes,
		uiPlacements,
	} = props;
	const alreadyAddedPointTypes: string[] = [];
	const alreadyAddedRankTypes: string[] = [];
	const alreadyAddedAchievementTypes: string[] = [];
	const allComponents: React.ReactNode[] = [];
	const locations = Array.isArray(location) ? location : [location];

	uiPlacements
		?.filter((placement) =>
			!isEmpty(placementId)
				? placement.id === placementId
				: placement.page === page &&
					locations.includes(placement.location ?? ''),
		)
		.forEach((uiPlacement) => {
			const components: React.ReactNode[] = [];

			const showHeading =
				showTypeHeading &&
				!NON_SIDEBAR_LOCATIONS.includes(uiPlacement.location ?? '');

			if (RewardType.POINT === uiPlacement.reward_type && pointTypes) {
				const pointTypesToShow = isEmpty(uiPlacement.types)
					? Object.keys(pointTypes)
					: uiPlacement.types;

				const pointItems: React.ReactNode[] = [];

				if (showHeading && pointTypesToShow?.length) {
					components.push(
						<Flex justifyContent={'flex-start'} alignItems={'center'}>
							<Text
								color={'oxford-night'}
								fontWeight={'semibold'}
								fontSize={'md'}
							>
								{__('Points', 'learning-management-system')}
							</Text>
						</Flex>,
					);
				}

				pointTypesToShow?.forEach((pointType) => {
					const pointTypeData = pointTypes[pointType];

					if (pointTypeData && !alreadyAddedPointTypes.includes(pointType)) {
						alreadyAddedPointTypes.push(pointType);
						pointItems.push(
							<React.Fragment key={uiPlacement.id + pointType}>
								{renderPoint(pointTypeData, uiPlacement)}
							</React.Fragment>,
						);
					}
				});

				if (pointItems.length > 0) {
					components.push(
						<React.Fragment key="items">
							{wrapItems(pointItems, uiPlacement)}
						</React.Fragment>,
					);
				}
			}

			if (RewardType.RANK === uiPlacement.reward_type && rankTypes) {
				const rankTypesToShow = isEmpty(uiPlacement.types)
					? Object.keys(rankTypes)
					: uiPlacement.types;

				const rankItems: React.ReactNode[] = [];

				if (showHeading && rankTypesToShow?.length) {
					components.push(
						<Flex justifyContent={'flex-start'} alignItems={'center'}>
							<Text
								color={'oxford-night'}
								fontWeight={'semibold'}
								fontSize={'md'}
							>
								{__('Ranks', 'learning-management-system')}
							</Text>
						</Flex>,
					);
				}

				rankTypesToShow?.forEach((rankType) => {
					const rankTypeData = rankTypes[rankType];

					if (rankTypeData && !alreadyAddedRankTypes.includes(rankType)) {
						alreadyAddedRankTypes.push(rankType);
						rankItems.push(
							<React.Fragment key={uiPlacement.id + rankType}>
								{renderRank(rankTypeData, uiPlacement)}
							</React.Fragment>,
						);
					}
				});

				if (rankItems.length > 0) {
					components.push(
						<React.Fragment key="items">
							{wrapItems(rankItems, uiPlacement)}
						</React.Fragment>,
					);
				}
			}

			if (
				RewardType.ACHIEVEMENT === uiPlacement.reward_type &&
				achievementTypes
			) {
				const achievementTypesToShow = isEmpty(uiPlacement.types)
					? Object.keys(achievementTypes)
					: uiPlacement.types;

				const achievementItems: React.ReactNode[] = [];

				if (showHeading && achievementTypesToShow?.length) {
					components.push(
						<Flex justifyContent={'flex-start'} alignItems={'center'} mr={1}>
							<Text
								color={'oxford-night'}
								fontWeight={'semibold'}
								fontSize={'md'}
							>
								{__('Achievements', 'learning-management-system')}
							</Text>
						</Flex>,
					);
				}

				achievementTypesToShow?.forEach((achievementType) => {
					const achievementTypeData = achievementTypes[achievementType];

					if (
						achievementTypeData &&
						!alreadyAddedAchievementTypes.includes(achievementType)
					) {
						alreadyAddedAchievementTypes.push(achievementType);

						achievementTypeData.achievements?.forEach((achievement, index) => {
							achievementItems.push(
								<React.Fragment key={uiPlacement.id + achievementType + index}>
									{renderAchievement(
										achievement,
										achievementTypeData,
										uiPlacement,
									)}
								</React.Fragment>,
							);
						});
					}
				});

				if (achievementItems.length > 0) {
					components.push(
						<React.Fragment key="items">
							{wrapItems(achievementItems, uiPlacement)}
						</React.Fragment>,
					);
				}
			}

			allComponents.push(
				<React.Fragment key={uiPlacement.id}>
					{wrapPlacementGroup(components, uiPlacement)}
				</React.Fragment>,
			);
		});

	if (isEmpty(allComponents)) {
		return null;
	}

	return <>{render(allComponents)}</>;
};

export default PlacementOnLocation;
