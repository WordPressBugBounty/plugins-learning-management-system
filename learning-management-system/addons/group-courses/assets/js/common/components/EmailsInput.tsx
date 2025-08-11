import {
	Alert,
	AlertIcon,
	Box,
	FormControl,
	FormLabel,
	IconButton,
	Input,
	InputGroup,
	InputRightElement,
	Stack,
	Tag,
	TagCloseButton,
	TagLabel,
	Text,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { useFormContext } from 'react-hook-form';
import { MdOutlineKeyboardReturn } from 'react-icons/md';
import localized from '../../../../../../assets/js/account/utils/global';

interface Props {
	defaultValue?: string[];
	maxGroupSize?: number;
	disabled?: boolean;
}

const EmailsInput: React.FC<Props> = ({
	defaultValue = [],
	maxGroupSize,
	disabled = false,
}) => {
	const {
		setValue,
		watch,
		trigger,
		formState: { errors },
		setError,
		clearErrors,
	} = useFormContext();
	const [input, setInput] = useState('');
	const emails = watch('emails') || defaultValue;
	// Use prop if provided, otherwise fallback to global limit
	const groupLimit = maxGroupSize || localized?.group_courses?.group_limit || 0;

	const isValidEmail = (email: string) => {
		const re =
			/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		return re.test(email.toLowerCase());
	};

	const handleAddEmail = () => {
		if (!isValidEmail(input)) {
			setError('emails', {
				type: 'manual',
				message: __('Invalid email address.', 'learning-management-system'),
			});
			return;
		}

		if (emails.includes(input)) {
			setError('emails', {
				type: 'manual',
				message: __('Email already added.', 'learning-management-system'),
			});
			return;
		}

		if (groupLimit > 0 && emails.length >= groupLimit) {
			setError('emails', {
				type: 'manual',
				message: __(
					`Maximum group size of ${groupLimit} members reached`,
					'learning-management-system',
				),
			});
			return;
		}

		const updatedEmails = [...emails, input];
		setValue('emails', updatedEmails, { shouldValidate: true });
		setInput('');
		clearErrors('emails');
		trigger('emails');
	};

	const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
		setInput(e.target.value);

		if (e.target.value.length === 0 || isValidEmail(e.target.value)) {
			clearErrors(['emails']);
		}
	};

	const handleRemoveEmail = (emailToRemove: string) => {
		const updatedEmails = emails.filter(
			(email: string) => email !== emailToRemove,
		);

		setValue('emails', updatedEmails, { shouldValidate: true });
		trigger('emails');
	};

	return (
		<FormControl isInvalid={Boolean(errors.emails)}>
			<FormLabel>
				{__('Members', 'learning-management-system')}
				{groupLimit > 0
					? ` (${__('Max', 'learning-management-system')}: ${groupLimit})`
					: ` (${__('Unlimited', 'learning-management-system')})`}
			</FormLabel>
			<Stack spacing={2}>
				{disabled && (
					<Alert status="info" borderRadius="md">
						<AlertIcon />
						<Text fontSize="sm">
							{__(
								'Group is pending approval. Members cannot be modified until the group is approved.',
								'learning-management-system',
							)}
						</Text>
					</Alert>
				)}
				<InputGroup>
					<Input
						color={'gray.600'}
						value={input}
						placeholder={__('Add new member', 'learning-management-system')}
						onChange={handleInputChange}
						onKeyDown={(e) => {
							if (e.key === 'Enter') {
								e.preventDefault();
								handleAddEmail();
							}
						}}
						isDisabled={
							disabled || (groupLimit > 0 && emails.length >= groupLimit)
						}
					/>
					<InputRightElement>
						<IconButton
							aria-label={__('Add Email', 'learning-management-system')}
							icon={<MdOutlineKeyboardReturn size="24px" color="white" />}
							onClick={handleAddEmail}
							isDisabled={
								disabled ||
								!isValidEmail(input) ||
								emails.includes(input) ||
								(groupLimit > 0 && emails.length >= groupLimit)
							}
							variant="solid"
							colorScheme="primary"
						/>
					</InputRightElement>
				</InputGroup>
				{errors.emails && (
					<Text fontSize="sm" color="red.500" mt={2}>
						{errors.emails.message?.toString()}
					</Text>
				)}
				{emails.length > 0 && (
					<Box
						display={'flex'}
						justifyContent={'flex-start'}
						flexWrap={'wrap'}
						bgColor={'muted'}
						maxH={'200px'}
						overflowY={'auto'}
						px={3}
						py={2}
						mt={2}
						borderRadius={'lg'}
					>
						{emails.map((email: string, index: number) => (
							<Tag
								colorScheme="blue"
								key={email}
								bgColor={'white'}
								mx={1}
								my={2}
								p={2}
							>
								<TagLabel color={'gray.600'}>{email}</TagLabel>
								<TagCloseButton
									color={'gray'}
									onClick={() => handleRemoveEmail(email)}
									isDisabled={disabled}
								/>
							</Tag>
						))}
					</Box>
				)}
			</Stack>
		</FormControl>
	);
};

export default EmailsInput;
