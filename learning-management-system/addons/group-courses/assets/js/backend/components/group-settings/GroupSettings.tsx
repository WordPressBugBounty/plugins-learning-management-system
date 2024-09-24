import { Box, Container, Stack, useToast } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { useMutation, useQuery } from 'react-query';
import {
	Header,
	HeaderPrimaryButton,
	HeaderRightSection,
	HeaderTop,
} from '../../../../../../../assets/js/back-end/components/common/Header';
import API from '../../../../../../../assets/js/back-end/utils/api';
import { urls } from '../../../constants/urls';
import { GroupSettingsSchema } from '../../../types/group';
import LeftHeader from '../LeftHeader';
import { SkeletonSetting } from '../Skeleton/SkeletonSetting';
import EnrollmentStatusControl from './EnrollmentStatusControl';
import MaxMembers from './MaxMembers';

const GroupSettings = () => {
	const toast = useToast();

	const settingsAPI = new API(urls.settings);
	const methods = useForm<GroupSettingsSchema>();

	const updateGroupSettingsMutation = useMutation(
		(data: GroupSettingsSchema) => settingsAPI.store(data),
		{
			onSuccess: () => {
				toast({
					title: __(
						'Group Course Settings Updated.',
						'learning-management-system',
					),
					isClosable: true,
					status: 'success',
				});
			},
			onError: (error: any) => {
				const message: any = error?.message
					? error?.message
					: error?.data?.message;

				toast({
					title: __(
						'Could not update the group settings.',
						'learning-management-system',
					),
					description: message ? `${message}` : undefined,
					status: 'error',
					isClosable: true,
				});
			},
		},
	);
	const groupSettingQuery = useQuery('groupCoursesSettings', () =>
		settingsAPI.get(),
	);

	const onSubmit = (data: GroupSettingsSchema) => {
		updateGroupSettingsMutation.mutate(data);
	};

	return groupSettingQuery.isSuccess ? (
		<Stack direction="column" spacing="8" alignItems="center">
			<Header>
				<HeaderTop>
					<LeftHeader />
					<HeaderRightSection>
						<HeaderPrimaryButton onClick={methods.handleSubmit(onSubmit)}>
							{__('Save Setting', 'learning-management-system')}
						</HeaderPrimaryButton>
					</HeaderRightSection>
				</HeaderTop>
			</Header>

			<Container maxW="container.xl">
				<Stack direction="column" spacing="6">
					<FormProvider {...methods}>
						<form onSubmit={methods.handleSubmit(onSubmit)}>
							<Stack
								direction={['column', 'column', 'column', 'row']}
								spacing={8}
							>
								<Box bg="white" p="10" shadow="box" gap="6" width="full">
									<Stack direction="column" spacing="6" pb="6">
										<MaxMembers
											defaultValue={groupSettingQuery?.data?.max_members}
										/>
										<EnrollmentStatusControl
											onMemberChange={
												groupSettingQuery?.data
													?.deactivate_enrollment_on_member_change
											}
											onStatusChange={
												groupSettingQuery?.data
													?.deactivate_enrollment_on_status_change
											}
										/>
									</Stack>
								</Box>
							</Stack>
						</form>
					</FormProvider>
				</Stack>
			</Container>
		</Stack>
	) : (
		<SkeletonSetting />
	);
};

export default GroupSettings;
