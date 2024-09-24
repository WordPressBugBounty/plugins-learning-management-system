import { Box, Collapse, Container, Stack, useToast } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { useMutation, useQuery, useQueryClient } from 'react-query';
import {
	Header,
	HeaderPrimaryButton,
	HeaderRightSection,
	HeaderTop,
} from '../../../../../../assets/js/back-end/components/common/Header';
import API from '../../../../../../assets/js/back-end/utils/api';
import { urls } from '../../constants/urls';
import { MultipleCurrencySettingsSchema } from '../../types/multiCurrency';
import LeftHeader from '../LeftHeader';
import { SkeletonSetting } from '../Skeleton/SkeletonSetting';
import Country from './Country';
import MaxMind from './MaxMind';
import TestModeControl from './TestModeControl';

const MultipleCurrencySettings = () => {
	const [testModeWatch, setTestModeWatch] = useState(false);
	const toast = useToast();
	const queryClient = useQueryClient();

	const settingsAPI = new API(urls.settings);
	const methods = useForm<MultipleCurrencySettingsSchema>();

	const updateSettingsMutation = useMutation(
		(data: MultipleCurrencySettingsSchema) => settingsAPI.store(data),
		{
			onSuccess: () => {
				toast({
					title: __(
						'Settings updated successfully.',
						'learning-management-system',
					),
					isClosable: true,
					status: 'success',
				});

				queryClient.invalidateQueries(`multipleCurrencySettings`);
			},
			onError: (error: any) => {
				const message: any = error?.message
					? error?.message
					: error?.data?.message;

				toast({
					title: __(
						'Could not update the settings.',
						'learning-management-system',
					),
					description: message ? `${message}` : undefined,
					status: 'error',
					isClosable: true,
				});
			},
		},
	);

	const settingQuery = useQuery('multipleCurrencySettings', () =>
		settingsAPI.get(),
	);

	const onSubmit = (data: MultipleCurrencySettingsSchema) => {
		updateSettingsMutation.mutate(data);
	};

	return settingQuery.isSuccess ? (
		<Stack direction="column" spacing="8" alignItems="center">
			<Header>
				<HeaderTop>
					<LeftHeader />
					<HeaderRightSection>
						<HeaderPrimaryButton
							onClick={methods.handleSubmit(onSubmit)}
							isLoading={updateSettingsMutation.isLoading}
						>
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
										<MaxMind maxmind={settingQuery?.data?.maxmind} />
										<TestModeControl
											setTestModeWatch={setTestModeWatch}
											defaultValue={settingQuery?.data?.test_mode?.enabled}
										/>
										<Collapse in={testModeWatch} animateOpacity>
											<Country
												defaultValue={settingQuery?.data?.test_mode?.country}
												testModeWatch={testModeWatch}
											/>
										</Collapse>
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

export default MultipleCurrencySettings;
