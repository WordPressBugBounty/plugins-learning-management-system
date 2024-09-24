import { HStack, Image, Stack, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Col, Row } from 'react-grid-system';
import { BiInfoCircle } from 'react-icons/bi';
import AccountCountBox from '../../../../assets/js/account/common/AccountCountBox';
import PageTitle from '../../../../assets/js/account/common/PageTitle';
import { isEmpty } from '../../../../assets/js/back-end/utils/utils';
import PlacementsInAccountPage from '../components/PlacementsInAccountPage';
import { AccountPageLocation } from '../enums/enums';

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
						title={pointTypeData.plural_name}
						count={pointTypeData.points}
						icon={
							pointTypeData.image_url ? (
								<Image
									src={pointTypeData.image_url}
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
						title={rankTypeData.singular_name}
						count={rankTypeData.rank}
						icon={
							rankTypeData.image_url ? (
								<Image
									src={rankTypeData.image_url}
									width="100%"
									height="100%"
									borderRadius="10px"
								/>
							) : null
						}
					/>
				</Col>
			)}
			renderAchievement={(achievementData) => (
				<Col xl={3} lg={4} md={4} sm={10}>
					<AccountCountBox
						isGamipressActive
						showBg={false}
						title={achievementData.label}
						count={''}
						icon={
							achievementData.image_url ? (
								<Image
									src={achievementData.image_url}
									width="100%"
									height="100%"
									borderRadius="10px"
								/>
							) : null
						}
					/>
				</Col>
			)}
			wrapPlacementGroup={(contents, placement) => (
				<>
					<PageTitle title={placement.title || ''} />

					<Stack direction="column" spacing="4">
						<Stack
							direction={{
								base: 'column',
								sm: 'row',
								md: 'row',
								lg: 'row',
							}}
							spacing="4"
							justify={{
								base: 'start',
								sm: 'start',
								md: 'start',
								lg: 'space-between',
							}}
							alignItems={{
								base: 'left',
								sm: 'left',
								md: 'center',
								lg: 'center',
							}}
						></Stack>
						{isEmpty(contents) ? (
							<HStack bgColor="gray.100" p="2">
								<BiInfoCircle />
								<Text fontWeight="md">
									{__('Not found.', 'learning-management-system')}
								</Text>
							</HStack>
						) : (
							<Row
								gutterWidth={30}
								justify="start"
								direction="row"
								style={{ gap: 2, rowGap: '20px' }}
							>
								{contents}
							</Row>
						)}
					</Stack>
				</>
			)}
		/>
	);
};

export default AccountPageTabContent;
