import {
	Box,
	FormErrorMessage,
	FormLabel,
	Icon,
	Skeleton,
	Tooltip,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useMemo } from 'react';
import { Controller, useFormContext } from 'react-hook-form';
import { BiInfoCircle } from 'react-icons/bi';
import { useQuery } from 'react-query';
import Select from 'react-select';
import FormControlTwoCol from '../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import { infoIconStyles } from '../../../../../../assets/js/back-end/config/styles';
import urls from '../../../../../../assets/js/back-end/constants/urls';
import { CurrenciesSchema } from '../../../../../../assets/js/back-end/schemas';
import API from '../../../../../../assets/js/back-end/utils/api';
import { MultipleCurrencySettingsSchema } from '../../types/multiCurrency';

interface CountryOption {
	value: string;
	label: string;
}

interface Props {
	defaultValue: CountryOption;
	testModeWatch: boolean;
}

const Country: React.FC<Props> = ({ defaultValue, testModeWatch }) => {
	const countriesAPI = new API(urls.countries);
	const {
		control,
		setValue,
		formState: { errors },
	} = useFormContext<MultipleCurrencySettingsSchema>();

	const countriesQuery = useQuery('countries', () => countriesAPI.list());

	const countryOptions = useMemo(() => {
		return countriesQuery.isSuccess
			? countriesQuery.data?.map((country: CurrenciesSchema) => ({
					value: country.code,
					label: country.name,
				}))
			: [];
	}, [countriesQuery.isSuccess, countriesQuery.data]);

	const noOptionsMessage = () =>
		__('No country found.', 'learning-management-system');

	return (
		<FormControlTwoCol
			isInvalid={!!errors?.test_mode?.country && testModeWatch}
		>
			<FormLabel>
				{__('Test Country', 'learning-management-system')}
				<Tooltip
					label={__(
						'Select the country for testing purposes.',
						'learning-management-system',
					)}
					hasArrow
					fontSize="xs"
				>
					<Box as="span" sx={infoIconStyles}>
						<Icon as={BiInfoCircle} />
					</Box>
				</Tooltip>
			</FormLabel>
			{countriesQuery.isLoading ? (
				<Skeleton height="40px" width="100%" />
			) : (
				<Controller
					name="test_mode.country"
					rules={
						testModeWatch
							? {
									required: __(
										'Please select a country.',
										'learning-management-system',
									),
								}
							: {}
					}
					control={control}
					defaultValue={defaultValue?.value}
					render={({ field: { onChange, value } }) => (
						<Select
							onChange={(selectedOption: any) => {
								setValue('test_mode.country', selectedOption?.value);
							}}
							options={countryOptions}
							isClearable={true}
							isSearchable={true}
							placeholder={__('Select a country', 'learning-management-system')}
							noOptionsMessage={noOptionsMessage}
							defaultValue={defaultValue}
						/>
					)}
				/>
			)}
			{testModeWatch && (
				<FormErrorMessage>
					{errors?.test_mode?.country?.message + ''}
				</FormErrorMessage>
			)}
		</FormControlTwoCol>
	);
};

export default Country;
