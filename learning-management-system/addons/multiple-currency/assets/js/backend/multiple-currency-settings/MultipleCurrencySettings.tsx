import { Box, Collapse, Container, Stack, useToast } from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import IndividualSectionSettingsWrapper from '../../../../../../assets/js/back-end/components/IndividualSectionSettingsWrapper';
import {
	Header,
	HeaderTop,
} from '../../../../../../assets/js/back-end/components/common/Header';
import { useWarnUnsavedChanges } from '../../../../../../assets/js/back-end/hooks/useWarnUnSavedChanges';
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

	const updateSettingsMutation = useMutation({
		mutationFn: (data: MultipleCurrencySettingsSchema) =>
			settingsAPI.store(data),
		...{
			onSuccess: () => {
				methods.reset(methods.getValues());
				toast({
					title: __(
						'Settings updated successfully.',
						'learning-management-system',
					),
					isClosable: true,
					status: 'success',
				});

				queryClient.invalidateQueries({
					queryKey: [`multipleCurrencySettings`],
				});
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
	});

	const settingQuery = useQuery({
		queryKey: ['multipleCurrencySettings'],
		queryFn: () => settingsAPI.get(),
	});

	const onSubmit = (data: MultipleCurrencySettingsSchema) => {
		updateSettingsMutation.mutate(data);
	};

	useWarnUnsavedChanges(methods.formState.isDirty);

	useEffect(() => {
		if (settingQuery?.isSuccess) {
			methods.reset(methods.getValues());
		}

		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [settingQuery?.isSuccess]);

	return settingQuery.isSuccess ? (
		<Stack direction="column" spacing="8" alignItems="center">
			<Header>
				<HeaderTop>
					<LeftHeader />
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
										isLoading={settingQuery.isLoading}
										isSaveActionPending={updateSettingsMutation.isPending}
										py={12}
									>
										<Stack direction="column" spacing="6">
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

export default MultipleCurrencySettings;
