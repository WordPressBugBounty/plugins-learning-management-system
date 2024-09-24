import {
	Box,
	FormControl,
	FormErrorMessage,
	FormLabel,
	IconButton,
	Input,
	InputGroup,
	InputRightElement,
	Stack,
	Tag,
	TagCloseButton,
	TagLabel,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { useFormContext } from 'react-hook-form';
import { MdOutlineKeyboardReturn } from 'react-icons/md';
import localized from '../../../../../../assets/js/account/utils/global';

interface Props {
	defaultValue?: string[];
}

const EmailsInput: React.FC<Props> = ({ defaultValue = [] }) => {
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
	const groupLimit = localized?.group_courses?.group_limit || 0;

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
				message: __('Group limit reached.', 'learning-management-system'),
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
				{`${__('Add Members', 'learning-management-system')}${
					groupLimit > 0
						? ` ( ${__('Group limit:', 'learning-management-system')} ${
								emails.length
							}/${groupLimit})`
						: ''
				}`}
			</FormLabel>
			<Stack spacing={2} direction="row" flexWrap="wrap">
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
					/>
					<InputRightElement>
						<IconButton
							aria-label={__('Add Email', 'learning-management-system')}
							icon={<MdOutlineKeyboardReturn size="24px" color="white" />}
							onClick={handleAddEmail}
							isDisabled={
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
					<FormErrorMessage>
						{errors.emails.message?.toString()}
					</FormErrorMessage>
				)}
				{emails.length && (
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
