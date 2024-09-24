import { Flex, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { GiAchievement, GiCrownCoin } from 'react-icons/gi';
import { TbMilitaryRank } from 'react-icons/tb';
import { isEmpty } from '../../../../assets/js/back-end/utils/utils';
import { RewardType } from '../enums/enums';

interface Props {
	location: string;
	page?: string;
	placementId?: string;
	renderPoint: (
		pointTypeData: { [key: string]: any },
		placement: UiPlacementData,
	) => React.ReactNode;
	renderRank: (
		rankTypeData: { [key: string]: any },
		placement: UiPlacementData,
	) => React.ReactNode;
	renderAchievement: (
		achievementData: {
			label: string;
			image_url: string;
		},
		achievementTypeData: { [key: string]: any },
		placement: UiPlacementData,
	) => React.ReactNode;
	wrapPlacementGroup?: (
		contents: React.ReactNode,
		placement: UiPlacementData,
	) => React.ReactNode;
	render?: (contents: React.ReactNode) => React.ReactNode;

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

const PlacementOnLocation: React.FC<Props> = (props) => {
	const {
		page,
		location,
		renderPoint,
		renderRank,
		renderAchievement,
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

	uiPlacements
		?.filter((placement) =>
			!isEmpty(placementId)
				? placement.id === placementId
				: placement.page === page && placement.location === location,
		)
		.forEach((uiPlacement) => {
			const components: React.ReactNode[] = [];

			if (RewardType.POINT === uiPlacement.reward_type && pointTypes) {
				const pointTypesToShow = isEmpty(uiPlacement.types)
					? Object.keys(pointTypes)
					: uiPlacement.types;

				if (
					pointTypesToShow?.length &&
					uiPlacement.location !== 'new-tab' &&
					uiPlacement.location !== 'dashboard-new-section' &&
					uiPlacement.location !== 'dashboard-card'
				) {
					components.push(
						<Flex justifyContent={'flex-start'} alignItems={'center'}>
							<Text color={'black'} fontWeight={'semibold'} mr={1}>
								{__('Points', 'learning-management-system')}
							</Text>
							<GiCrownCoin size={16} color={'orange'} />
						</Flex>,
					);
				}

				pointTypesToShow?.forEach((pointType) => {
					const pointTypeData = pointTypes[pointType];

					if (pointTypeData && !alreadyAddedPointTypes.includes(pointType)) {
						alreadyAddedPointTypes.push(pointType);
						components.push(
							<React.Fragment key={uiPlacement.id + pointType}>
								{renderPoint(pointTypeData, uiPlacement)}
							</React.Fragment>,
						);
					}
				});
			}

			if (RewardType.RANK === uiPlacement.reward_type && rankTypes) {
				const rankTypesToShow = isEmpty(uiPlacement.types)
					? Object.keys(rankTypes)
					: uiPlacement.types;

				if (
					rankTypesToShow?.length &&
					uiPlacement.location !== 'new-tab' &&
					uiPlacement.location !== 'dashboard-new-section' &&
					uiPlacement.location !== 'dashboard-card'
				) {
					components.push(
						<Flex justifyContent={'flex-start'} alignItems={'center'}>
							<Text color={'black'} fontWeight={'semibold'} mr={1}>
								{__('Ranks', 'learning-management-system')}
							</Text>
							<TbMilitaryRank size={18} color={'blue'} />
						</Flex>,
					);
				}

				rankTypesToShow?.forEach((rankType) => {
					const rankTypeData = rankTypes[rankType];

					if (rankTypeData && !alreadyAddedRankTypes.includes(rankType)) {
						alreadyAddedRankTypes.push(rankType);
						components.push(
							<React.Fragment key={uiPlacement.id + rankType}>
								{renderRank(rankTypeData, uiPlacement)}
							</React.Fragment>,
						);
					}
				});
			}

			if (
				RewardType.ACHIEVEMENT === uiPlacement.reward_type &&
				achievementTypes
			) {
				const achievementTypesToShow = isEmpty(uiPlacement.types)
					? Object.keys(achievementTypes)
					: uiPlacement.types;

				if (
					achievementTypesToShow?.length &&
					uiPlacement.location !== 'new-tab' &&
					uiPlacement.location !== 'dashboard-new-section' &&
					uiPlacement.location !== 'dashboard-card'
				) {
					components.push(
						<Flex justifyContent={'flex-start'} alignItems={'center'} mr={1}>
							<Text color={'black'} fontWeight={'semibold'}>
								{__('Achievements', 'learning-management-system')}
							</Text>
							<GiAchievement size={20} color={'green'} />
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
							components.push(
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
