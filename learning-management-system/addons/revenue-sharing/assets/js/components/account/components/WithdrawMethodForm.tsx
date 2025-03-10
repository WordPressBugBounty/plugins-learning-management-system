import {
	Button,
	FormControl,
	FormLabel,
	Input,
	Modal,
	ModalBody,
	ModalCloseButton,
	ModalContent,
	ModalFooter,
	ModalHeader,
	ModalOverlay,
	Select,
	Stack,
	Text,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useForm, useWatch } from 'react-hook-form';
import localized from '../../../../../../../assets/js/account/utils/global';
import urls from '../../../../../../../assets/js/back-end/constants/urls';
import API from '../../../../../../../assets/js/back-end/utils/api';
import { WithdrawPreferenceDataMap } from '../../../types/withdraw';

const WITHDRAW_METHODS = [
	{ id: 'paypal', name: __('Paypal', 'learning-management-system') },
	{
		id: 'bank_transfer',
		name: __('Bank Transfer', 'learning-management-system'),
	},
	{ id: 'e_check', name: __('E-Check', 'learning-management-system') },
];

type Props = {
	data?: WithdrawPreferenceDataMap;
	isOpen: boolean;
	onClose: () => void;
};

const WithdrawMethodForm: React.FC<Props> = (props) => {
	const { data, onClose, isOpen } = props;
	const { register, handleSubmit, watch, control } =
		useForm<WithdrawPreferenceDataMap>();
	const queryClient = useQueryClient();
	const withdrawMethod = watch('method', data?.method ?? '');

	const watchedFields = useWatch({
		control,
		name: [
			'paypal_email',
			'physical_address',
			'bank_name',
			'account_name',
			'account_number',
			'iban',
			'swift_code',
		],
	});

	const [
		paypalEmail,
		physicalAddress,
		bankName,
		accountName,
		accountNumber,
		iban,
		swiftCode,
	] = watchedFields;

	const isFormValid = () => {
		switch (withdrawMethod) {
			case 'paypal':
				return !!paypalEmail;
			case 'e_check':
				return !!physicalAddress;
			case 'bank_transfer':
				return (
					!!bankName &&
					!!accountName &&
					!!accountNumber &&
					!!iban &&
					!!swiftCode
				);
			default:
				return false;
		}
	};

	const userAPI = new API(urls.currentUser);
	const toast = useToast();

	const updateWithdrawData = useMutation({
		mutationFn: (data: WithdrawPreferenceDataMap) =>
			userAPI.store({ withdraw_method_preference: data }),
		onSuccess() {
			queryClient.invalidateQueries({ queryKey: ['userProfile'] });
			onClose();
			toast({
				title: __(
					'Withdraw method updated successfully',
					'learning-management-system',
				),
				status: 'success',
				isClosable: true,
				containerStyle: { fontSize: 'sm' },
			});
		},
		onError(error: Error) {
			onClose();
			toast({
				status: 'error',
				isClosable: true,
				title: __(
					'Failed to update withdraw method',
					'learning-management-system',
				),
				description: error.message,
				containerStyle: { fontSize: 'sm' },
			});
		},
	});

	const onSubmit = (data: WithdrawPreferenceDataMap) => {
		updateWithdrawData.mutate(data);
	};

	return (
		<Modal isOpen={isOpen} onClose={onClose} isCentered>
			<ModalOverlay />
			<ModalContent>
				<ModalHeader px="10" pt={10}>
					{__('Withdraw Preference', 'learning-management-system')}
				</ModalHeader>
				<ModalCloseButton />
				<ModalBody
					px="10"
					pb={!localized.withdraw_methods?.length ? 10 : undefined}
				>
					{!localized.withdraw_methods?.length ? (
						<Text color="gray.500" fontSize="sm" mt="4">
							{__(
								"A withdrawal method hasn't been chosen yet. Kindly reach out to the Site Admin to select your preferred withdrawal option.",
								'learning-management-system',
							)}
						</Text>
					) : (
						<form onSubmit={handleSubmit(onSubmit)}>
							<Stack spacing="9" w="100%">
								<Stack direction="column" spacing="4">
									<FormControl>
										<FormLabel>
											{__('Withdraw Method', 'learning-management-system')}
										</FormLabel>
										<Select
											placeholder={__(
												'Select a withdraw method',
												'learning-management-system',
											)}
											{...register('method')}
											defaultValue={data?.method}
										>
											{WITHDRAW_METHODS.filter((x) =>
												localized.withdraw_methods?.includes(x.id),
											).map((x) => (
												<option key={x.id} value={x.id}>
													{x.name}
												</option>
											))}
										</Select>
									</FormControl>

									{'e_check' === withdrawMethod && (
										<FormControl>
											<FormLabel>
												{__('Physical Address', 'learning-management-system')}
											</FormLabel>
											<Input
												{...register('physical_address')}
												defaultValue={data?.physical_address}
											/>
										</FormControl>
									)}
									{'paypal' === withdrawMethod && (
										<FormControl>
											<FormLabel>
												{__(
													'Paypal Email Address',
													'learning-management-system',
												)}
											</FormLabel>
											<Input
												{...register('paypal_email')}
												defaultValue={data?.paypal_email}
											/>
										</FormControl>
									)}
									{'bank_transfer' === withdrawMethod && (
										<>
											<FormControl>
												<FormLabel>
													{__('Bank Name', 'learning-management-system')}
												</FormLabel>
												<Input
													{...register('bank_name')}
													defaultValue={data?.bank_name}
												/>
											</FormControl>
											<FormControl>
												<FormLabel>
													{__('Account Name', 'learning-management-system')}
												</FormLabel>
												<Input
													{...register('account_name')}
													defaultValue={data?.account_name}
												/>
											</FormControl>
											<FormControl>
												<FormLabel>
													{__('Account Number', 'learning-management-system')}
												</FormLabel>
												<Input
													{...register('account_number')}
													defaultValue={data?.account_number}
												/>
											</FormControl>
											<FormControl>
												<FormLabel>
													{__(
														'International Bank Account Number (IBAN)',
														'learning-management-system',
													)}
												</FormLabel>
												<Input
													{...register('iban')}
													defaultValue={data?.iban}
												/>
											</FormControl>
											<FormControl>
												<FormLabel>
													{__('BIC / SWIFT Code', 'learning-management-system')}
												</FormLabel>
												<Input
													{...register('swift_code')}
													defaultValue={data?.swift_code}
												/>
											</FormControl>
										</>
									)}
								</Stack>
							</Stack>
						</form>
					)}
				</ModalBody>

				{localized.withdraw_methods?.length ? (
					<ModalFooter
						display="flex"
						justifyContent="space-between"
						pb="10"
						pt="8"
						px="10"
					>
						<Button
							colorScheme="primary"
							variant="outline"
							mr={3}
							onClick={onClose}
							isDisabled={updateWithdrawData.isPending}
						>
							{__('Cancel', 'learning-management-system')}
						</Button>
						<Button
							colorScheme="primary"
							onClick={handleSubmit(onSubmit)}
							isLoading={updateWithdrawData.isPending}
							isDisabled={!isFormValid()}
						>
							{__('Save', 'learning-management-system')}
						</Button>
					</ModalFooter>
				) : null}
			</ModalContent>
		</Modal>
	);
};

export default WithdrawMethodForm;
