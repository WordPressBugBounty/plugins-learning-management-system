import { Image, List, ListItem, Stack, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Col, Row } from 'react-grid-system';
import AccountCountBox from '../../../../assets/js/account/common/AccountCountBox';
import EmptyCourse from '../../../../assets/js/account/common/EmptyCourse';
import PageTitle from '../../../../assets/js/account/common/PageTitle';
import { isEmpty } from '../../../../assets/js/back-end/utils/utils';
import PlacementsInAccountPage from '../components/PlacementsInAccountPage';
import { AccountPageLocation, RewardType } from '../enums/enums';

interface Props {
	placement: UiPlacementData;
}

const AccountPageTabContent: React.FC<Props> = (props) => {
	const { placement } = props;

	return (
		<PlacementsInAccountPage
			placementId={placement.id}
			location={AccountPageLocation.NEW_TAB}
			renderPoint={(pointTypeData) => (
				<Col xl={3} lg={4} md={4} sm={10}>
					<AccountCountBox
						isGamipressActive
						showBg={false}
						description={pointTypeData.plural_name}
						title={pointTypeData.points}
						icon={
							pointTypeData.image_url ? (
								<Image
									src={pointTypeData.image_url}
									alt=""
									width="100%"
									height="100%"
									borderRadius="10px"
								/>
							) : null
						}
					/>
				</Col>
			)}
			renderRank={(rankTypeData) => (
				<Col xl={3} lg={4} md={4} sm={10}>
					<AccountCountBox
						isGamipressActive
						showBg={false}
						description={rankTypeData.singular_name}
						title={rankTypeData.rank}
						icon={
							rankTypeData.image_url ? (
								<Image
									src={rankTypeData.image_url}
									alt=""
									width="100%"
									height="100%"
									borderRadius="10px"
								/>
							) : null
						}
					/>
				</Col>
			)}
			renderAchievement={(achievementData, achievementTypeData) => (
				<ListItem
					_notFirst={{ borderTopWidth: '1px', borderColor: 'icy-blue-gray' }}
				>
					<Stack direction="row" align="center" spacing={4} px={5} py={3}>
						{achievementData.image_url ? (
							<Image
								src={achievementData.image_url}
								alt=""
								boxSize={10}
								objectFit="cover"
								rounded="full"
								flexShrink={0}
							/>
						) : null}
						<Stack spacing={0}>
							<Text fontSize="md" fontWeight="semibold" color="oxford-night">
								{achievementData.label}
							</Text>
							<Text fontSize="sm" color="saint-blue">
								{achievementTypeData.singular_name}
							</Text>
						</Stack>
					</Stack>
				</ListItem>
			)}
			wrapItems={(items, uiPlacement) =>
				RewardType.ACHIEVEMENT === uiPlacement.reward_type ? (
					<List
						borderWidth="1px"
						borderColor="icy-blue-gray"
						rounded="xl"
						bg="white !important"
					>
						{items}
					</List>
				) : (
					<Row
						gutterWidth={30}
						justify="start"
						direction="row"
						style={{ gap: 2, rowGap: '20px' }}
					>
						{items}
					</Row>
				)
			}
			wrapPlacementGroup={(contents, uiPlacement) => (
				<>
					<PageTitle title={uiPlacement.title || ''} />

					{isEmpty(contents) ? (
						<EmptyCourse
							text={__('No content found', 'learning-management-system')}
							showButton={false}
						/>
					) : (
						<Stack direction="column" spacing="4">
							{contents}
						</Stack>
					)}
				</>
			)}
		/>
	);
};

export default AccountPageTabContent;
