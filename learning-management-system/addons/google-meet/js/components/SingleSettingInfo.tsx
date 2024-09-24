import {
	Box,
	Flex,
	FormLabel,
	Icon,
	Input,
	Radio,
	RadioGroup,
	Select,
	Text,
	Tooltip,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Controller } from 'react-hook-form';
import { BiInfoCircle } from 'react-icons/bi';
import FormControlTwoCol from '../../../../assets/js/back-end/components/common/FormControlTwoCol';
import { infoIconStyles } from '../../../../assets/js/back-end/config/styles';

interface Props {
	field: {
		title: string;
		key: string;
		description: string;
		fieldType: string;
		radioFields?: { label: string; key: string | number }[];
		options?: string[];
	};
	index?: number;
	control: any;
	errors: object;
}

const inputFieldRendered = (
	type: string,
	field: any,
	radioFields: any,
	title: string,
	options: string[] | undefined,
) => {
	let selectedInput: any;

	switch (type) {
		case 'radio':
			selectedInput = (
				<RadioGroup {...field}>
					<Flex alignItems={'center'} flexWrap={'wrap'}>
						{radioFields?.map((radioField: any) => (
							<Radio
								key={String(radioField.key)}
								mr={3}
								my={1}
								value={String(radioField.key)}
							>
								{__(radioField.label, 'learning-management-system')}
							</Radio>
						))}
					</Flex>
				</RadioGroup>
			);
			break;

		case 'select':
			selectedInput = (
				<Select
					{...field}
					placeholder={__(`Select ${title}`, 'learning-management-system')}
				>
					{options?.map((option) => (
						<option key={option} value={option}>
							{__(option, 'learning-management-system')}
						</option>
					))}
				</Select>
			);
			break;

		default:
			selectedInput = <Input {...field} />;
			break;
	}

	return selectedInput;
};

const SingleSettingInfo: React.FC<Props> = ({
	field: { title, description, fieldType, key, options, radioFields },
	control,
	errors,
}) => {
	return (
		<FormControlTwoCol>
			<FormLabel display={'flex'} alignItems={'center'}>
				{__(title, 'learning-management-system')}
				<Tooltip
					label={__(description, 'learning-management-system')}
					hasArrow
					fontSize="xs"
				>
					<Box as="span" sx={infoIconStyles}>
						<Icon as={BiInfoCircle} />
					</Box>
				</Tooltip>
			</FormLabel>

			<Controller
				name={key}
				control={control}
				rules={{ required: `${title} field is required` }}
				render={({ field }) => (
					<Box display={'flex'} flexDirection={'column'}>
						{inputFieldRendered(fieldType, field, radioFields, title, options)}
						{errors[key] && (
							<Text mt={2} color={'red'}>
								{__(errors[key]?.message, 'learning-management-system')}
							</Text>
						)}
					</Box>
				)}
			/>
		</FormControlTwoCol>
	);
};

export default SingleSettingInfo;
