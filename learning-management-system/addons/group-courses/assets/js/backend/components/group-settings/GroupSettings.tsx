import { Box, Container, Stack, useToast } from '@chakra-ui/react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { useSearchParams } from 'react-router-dom';
import IndividualSectionSettingsWrapper from '../../../../../../../assets/js/back-end/components/IndividualSectionSettingsWrapper';
import FilterTabs from '../../../../../../../assets/js/back-end/components/common/FilterTabs';
import {
	Header,
	HeaderLeftSection,
	HeaderLogo,
	HeaderTop,
} from '../../../../../../../assets/js/back-end/components/common/Header';
import { Gear } from '../../../../../../../assets/js/back-end/constants/images';
import { useWarnUnsavedChanges } from '../../../../../../../assets/js/back-end/hooks/useWarnUnSavedChanges';
import API from '../../../../../../../assets/js/back-end/utils/api';
import { urls } from '../../../constants/urls';
import { GroupSettingsSchema } from '../../../types/group';
import { SkeletonSetting } from '../Skeleton/SkeletonSetting';
import EnrollmentStatusControl from './EnrollmentStatusControl';
import GroupBuyButtonText from './GroupBuyButtonText';
import GroupBuyHelperText from './GroupBuyHelperText';

export interface FilterParams {
	category?: string | number;
	search?: string;
	status?: string;
	isOnlyFree?: boolean;
	price?: string | number;
	per_page?: number;
	page?: number;
	orderby: string;
	order: 'asc' | 'desc';
}

const tabButtons: FilterTabs = [
	{
		status: 'any',
		name: __('All Groups', 'learning-management-system'),
		link: '/groups?status=any',
	},
	{
		status: 'publish',
		name: __('Published', 'learning-management-system'),
		link: '/groups?status=publish',
	},
	{
		status: 'draft',
		name: __('Draft', 'learning-management-system'),
		link: '/groups?status=draft',
	},
	{
		status: 'trash',
		name: __('Trash', 'learning-management-system'),
		link: '/groups?status=trash',
	},
	{
		status: 'settings',
		name: __('Settings', 'learning-management-system'),
		link: '/groups-settings',
		icon: <Gear height="20px" width="20px" fill="currentColor" />,
	},
];

const GroupSettings = () => {
	const toast = useToast();

	const settingsAPI = new API(urls.settings);
	const methods = useForm<GroupSettingsSchema>();
	const [filterParams, setFilterParams] = useState<FilterParams>({
		order: 'desc',
		orderby: 'date',
	});

	const [active, setActive] = useState('settings');
	const [searchParams] = useSearchParams();
	const currentTab = searchParams.get('status');
	const updateGroupSettingsMutation = useMutation({
		mutationFn: (data: GroupSettingsSchema) => settingsAPI.store(data),
		...{
			onSuccess: () => {
				methods.reset(methods.getValues());
				toast({
					title: __('Groups Settings Updated.', 'learning-management-system'),
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
	});
	const groupSettingQuery = useQuery({
		queryKey: ['groupCoursesSettings'],
		queryFn: () => settingsAPI.get(),
	});

	const onSubmit = (data: GroupSettingsSchema) => {
		updateGroupSettingsMutation.mutate(data);
	};

	useEffect(() => {
		if (currentTab) {
			setFilterParams((prevState) => ({
				...prevState,
				status: currentTab,
			}));
			setActive(currentTab);
		}
	}, [currentTab]);

	const onChangeCourseStatus = (status: string) => {
		setActive(status);
		setFilterParams({ ...filterParams, status: status });
	};

	useWarnUnsavedChanges(methods.formState.isDirty);

	useEffect(() => {
		if (groupSettingQuery?.isSuccess && groupSettingQuery?.data) {
			methods.reset(methods.getValues());
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [groupSettingQuery?.isSuccess, groupSettingQuery?.data]);

	return groupSettingQuery.isSuccess ? (
		<Stack direction="column" spacing="8" alignItems="center">
			<Header>
				<HeaderTop>
					<HeaderLeftSection gap="7">
						<HeaderLogo />
						<FilterTabs
							tabs={tabButtons}
							defaultActive={active}
							onTabChange={onChangeCourseStatus}
							counts={groupSettingQuery.data?.groups_count}
							isCounting={groupSettingQuery.isLoading}
						/>
					</HeaderLeftSection>
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
								<Box bg="white" shadow="box" gap="6" width="full">
									<IndividualSectionSettingsWrapper
										py={12}
										isLoading={groupSettingQuery.isLoading}
										isSaveActionPending={updateGroupSettingsMutation.isPending}
									>
										<Stack direction="column" spacing="6">
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
											 
											<GroupBuyButtonText
												defaultValue={
													groupSettingQuery?.data?.group_buy_button_text
												}
											/>
											<GroupBuyHelperText
												defaultValue={
													groupSettingQuery?.data?.group_buy_helper_text
												}
											/>
										</Stack>
									</IndividualSectionSettingsWrapper>
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
