import {
	Box,
	FormControl,
	FormErrorMessage,
	FormLabel,
	Icon,
	Skeleton,
	Stack,
	Tooltip,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useMemo } from 'react';
import { Controller, useFormContext } from 'react-hook-form';
import { BiInfoCircle } from 'react-icons/bi';
import { useQuery } from 'react-query';
import AsyncSelect from '../../../../../../assets/js/back-end/components/common/AsyncSelect';
import {
	infoIconStyles,
	reactSelectStyles,
} from '../../../../../../assets/js/back-end/config/styles';
import urls from '../../../../../../assets/js/back-end/constants/urls';
import { CountriesSchema } from '../../../../../../assets/js/back-end/schemas';
import API from '../../../../../../assets/js/back-end/utils/api';
import { isEmpty } from '../../../../../../assets/js/back-end/utils/utils';
import { PriceZoneSchema } from '../../types/multiCurrency';

interface SelectOption {
	value: string;
	label: string;
}

interface Props {
	defaultValue?: PriceZoneSchema['countries'];
	prizeZoneID?: number;
}

const Countries: React.FC<Props> = ({ defaultValue, prizeZoneID }) => {
	const {
		control,
		setValue,
		formState: { errors },
		trigger,
	} = useFormContext();

	const countriesAPI = new API(urls.countries);
	const countriesQuery = useQuery('countries', () =>
		countriesAPI.list({
			order: 'asc',
			per_page: 10,
			is_from_multiple_currency: true,
			price_zone_id: prizeZoneID,
		}),
	);

	const options: SelectOption[] = useMemo(() => {
		return countriesQuery.isSuccess
			? countriesQuery.data?.map((country: CountriesSchema) => {
					return {
						value: country.code,
						label: country.name,
					};
				})
			: [];
	}, [countriesQuery.isSuccess, countriesQuery.data]);

	useEffect(() => {
		if (defaultValue) {
			setValue(
				'countries',
				defaultValue?.map((country: any) => country?.value),
			);
		}
	}, [defaultValue, setValue]);

	return (
		<Stack spacing={2}>
			<FormControl isInvalid={!!errors.countries}>
				<FormLabel htmlFor="countries">
					{__('Countries', 'learning-management-system')}
					<Tooltip
						label={__(
							'These are countries inside this zone. Customers will be matched against these countries.',
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
						name="countries"
						rules={{
							required: __(
								'Please select at least one country.',
								'learning-management-system',
							),
						}}
						control={control}
						defaultValue={defaultValue?.map((country: any) => country?.value)}
						render={({ field: { onChange, value } }) => (
							<AsyncSelect
								loadingMessage={() =>
									__('Searching...', 'learning-management-system')
								}
								onChange={(selectedOption: any) => {
									const selectedValues = selectedOption?.map(
										(country: any) => country.value,
									);
									setValue('countries', selectedValues);
									trigger('countries');
								}}
								options={options}
								isMulti
								closeMenuOnSelect={false}
								isClearable
								styles={reactSelectStyles}
								defaultValue={defaultValue}
								defaultOptions={options}
								loadOptions={(searchValue, callback) => {
									if (isEmpty(searchValue)) {
										return callback([]);
									}

									const filteredOptions = options.filter((option) => {
										const optionValue = option.value.toLowerCase();
										const optionLabel = option.label.toLowerCase();
										const searchTerm = searchValue.toLowerCase();

										return (
											optionValue.includes(searchTerm) ||
											optionLabel.includes(searchTerm)
										);
									});

									callback(filteredOptions);
								}}
								noOptionsMessage={() =>
									__('No countries found.', 'learning-management-system')
								}
							/>
						)}
					/>
				)}
				<FormErrorMessage>
					{errors.countries && errors.countries.message?.toString()}
				</FormErrorMessage>
			</FormControl>
		</Stack>
	);
};

export default Countries;
