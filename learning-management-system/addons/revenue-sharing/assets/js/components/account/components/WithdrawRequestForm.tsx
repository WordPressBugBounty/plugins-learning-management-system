import {
	Button,
	FormControl,
	FormErrorMessage,
	FormLabel,
	InputGroup,
	InputLeftAddon,
	Modal,
	ModalBody,
	ModalCloseButton,
	ModalContent,
	ModalFooter,
	ModalHeader,
	ModalOverlay,
	NumberDecrementStepper,
	NumberIncrementStepper,
	NumberInput,
	NumberInputField,
	NumberInputStepper,
	Tooltip,
	useDisclosure,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { __, sprintf } from '@wordpress/i18n';
import React from 'react';
import { useForm } from 'react-hook-form';
import localized from '../../../../../../../assets/js/account/utils/global';
import { UserSchema } from '../../../../../../../assets/js/back-end/schemas';
import API from '../../../../../../../assets/js/back-end/utils/api';
import { urls } from '../../../constants/urls';

type Props = {
	data: UserSchema;
};

const WithdrawRequestForm: React.FC<Props> = (props) => {
	const { data } = props;
	const { isOpen, onClose, onOpen } = useDisclosure();

	const {
		register,
		handleSubmit,
		formState: { errors },
		reset,
	} = useForm();
	const toast = useToast();
	const queryClient = useQueryClient();

	const withdrawAPI = new API(urls.withdraws);

	const minWithdrawAmount = data?.revenue_sharing?.minimum_withdraw_amount ?? 0;
	const availableBalance = data?.revenue_sharing?.withdrawable_amount ?? 0;

	const onSubmit = (d: any) => {
		withdrawRequestMutation.mutate({
			...d,
			withdraw_method: data.revenue_sharing?.withdraw_method_preference,
		});
	};

	const withdrawRequestMutation = useMutation({
		mutationFn: (data: any) => withdrawAPI.store(data),
		...{
			onSuccess() {
				reset();
				queryClient.invalidateQueries({ queryKey: ['withdrawsList'] });
				onClose();
				toast({
					title: __(
						'Withdraw request sent successfully',
						'learning-management-system',
					),
					status: 'success',
					isClosable: true,
					containerStyle: {
						fontSize: 'sm',
					},
				});
			},
			onError(error: Error) {
				reset();
				onClose();
				toast({
					title: __(
						'Failed to send withdraw request',
						'learning-management-system',
					),
					status: 'success',
					isClosable: true,
					description: error.message,
					containerStyle: {
						fontSize: 'sm',
					},
				});
			},
		},
	});
	return (
		<>
			<Tooltip
				label={__('Insufficient balance', 'learning-management-system')}
				placement="top"
				isDisabled={
					Number(availableBalance) &&
					Number(availableBalance) >= Number(minWithdrawAmount)
						? true
						: false
				}
			>
				<Button
					isDisabled={
						!Number(availableBalance) ||
						Number(availableBalance) < Number(minWithdrawAmount)
							? true
							: false
					}
					colorScheme="primary"
					onClick={onOpen}
				>
					{__('Withdraw Now', 'learning-management-system')}
				</Button>
			</Tooltip>
			<Modal
				isOpen={isOpen}
				onClose={() => {
					onClose();
					reset();
				}}
				isCentered
			>
				<ModalOverlay />
				<ModalContent>
					<ModalHeader px="10" pt="10">
						{__('Withdraw Request', 'learning-management-system')}
					</ModalHeader>
					<ModalCloseButton />
					<ModalBody px="10">
						<form onSubmit={handleSubmit(onSubmit)}>
							<FormControl isInvalid={!!errors?.withdraw_amount}>
								<FormLabel>
									{__('Amount', 'learning-management-system')}
								</FormLabel>
								<InputGroup>
									<InputLeftAddon>{localized.currency.symbol}</InputLeftAddon>
									<NumberInput flex="1">
										<NumberInputField
											{...register('withdraw_amount', {
												valueAsNumber: true,
												required: __(
													'Amount is required',
													'learning-management-system',
												),
												validate(value) {
													if (minWithdrawAmount && value < minWithdrawAmount) {
														return sprintf(
															__(
																'Amount must be at least %s',
																'learning-management-system',
															),
															data?.revenue_sharing
																?.minimum_withdraw_amount_formatted,
														);
													}
													if (value > availableBalance) {
														return sprintf(
															__(
																'Amount must be at most %s',
																'learning-management-system',
															),
															data?.revenue_sharing
																?.withdrawable_amount_formatted,
														);
													}
													return true;
												},
											})}
											defaultValue={undefined}
											borderRadius="0"
										/>
										<NumberInputStepper>
											<NumberIncrementStepper />
											<NumberDecrementStepper />
										</NumberInputStepper>
									</NumberInput>
								</InputGroup>
								{errors?.withdraw_amount && (
									<FormErrorMessage>
										{errors?.withdraw_amount.message as string}
									</FormErrorMessage>
								)}
							</FormControl>
						</form>
					</ModalBody>
					<ModalFooter
						px="10"
						display="flex"
						justifyContent="space-between"
						pb="10"
						pt="8"
					>
						<Button
							colorScheme="primary"
							variant="outline"
							mr={3}
							onClick={onClose}
							isDisabled={withdrawRequestMutation.isPending}
						>
							{__('Cancel', 'learning-management-system')}
						</Button>
						<Button
							colorScheme="primary"
							isLoading={withdrawRequestMutation.isPending}
							onClick={handleSubmit(onSubmit)}
						>
							{__('Submit', 'learning-management-system')}
						</Button>
					</ModalFooter>
				</ModalContent>
			</Modal>
		</>
	);
};

export default WithdrawRequestForm;
