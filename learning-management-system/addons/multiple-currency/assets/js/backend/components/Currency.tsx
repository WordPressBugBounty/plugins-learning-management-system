import {
	Box,
	FormControl,
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
import { infoIconStyles } from '../../../../../../assets/js/back-end/config/styles';
import urls from '../../../../../../assets/js/back-end/constants/urls';
import { CurrenciesSchema } from '../../../../../../assets/js/back-end/schemas';
import API from '../../../../../../assets/js/back-end/utils/api';
import { PriceZoneSchema } from '../../types/multiCurrency';

interface Props {
	defaultValue?: PriceZoneSchema['currency'];
}

const Currency: React.FC<Props> = ({ defaultValue }) => {
	const currenciesAPI = new API(urls.currencies);
	const {
		control,
		setValue,
		formState: { errors },
		trigger,
	} = useFormContext();

	const currenciesQuery = useQuery('currencies', () => currenciesAPI.list());

	const currencyOptions = useMemo(() => {
		return currenciesQuery.isSuccess
			? currenciesQuery.data?.map((currency: CurrenciesSchema) => ({
					value: currency.code,
					label: `${currency.name} (${currency.symbol})`,
				}))
			: [];
	}, [currenciesQuery.isSuccess, currenciesQuery.data]);

	const noOptionsMessage = () =>
		__('No currency found.', 'learning-management-system');

	return (
		<FormControl isInvalid={!!errors?.currency}>
			<FormLabel>
				{__('Currency', 'learning-management-system')}
				<Tooltip
					label={__(
						'Choose the currency of this zone. Customers from the countries of this zone will see the prices and pay in this currency.',
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
			{currenciesQuery.isLoading ? (
				<Skeleton height="40px" width="100%" />
			) : (
				<Controller
					name="currency"
					rules={{
						required: __(
							'Please select a currency.',
							'learning-management-system',
						),
					}}
					control={control}
					defaultValue={defaultValue?.value}
					render={({ field: { onChange, value } }) => (
						<Select
							onChange={(selectedOption: any) => {
								setValue('currency', selectedOption?.value);
								trigger('currency');
							}}
							options={currencyOptions}
							isClearable={true}
							isSearchable={true}
							placeholder={__(
								'Select a currency',
								'learning-management-system',
							)}
							noOptionsMessage={noOptionsMessage}
							defaultValue={defaultValue}
						/>
					)}
				/>
			)}
			<FormErrorMessage>{errors?.currency?.message + ''}</FormErrorMessage>
		</FormControl>
	);
};

export default Currency;
